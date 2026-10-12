<?php

use App\Eocs\EocsClaim;
use App\Eocs\EocsOrder;
use App\Eocs\EocsOrderItem;
use App\Eocs\EocsShipment;
use App\Knowledge\CustomerGroup;
use App\Knowledge\Retrieval\CaseFacts;
use App\Knowledge\Retrieval\CaseFactsBuilder;
use App\Knowledge\Retrieval\Evaluation;
use App\Knowledge\Retrieval\OrderFacts;
use App\Knowledge\Retrieval\Tri;
use App\Knowledge\Retrieval\WhenEvaluator;
use App\Zammad\ArticleKind;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use Carbon\CarbonImmutable;
use Illuminate\Support\HtmlString;

const FACTS_NOW = '2026-10-20 12:00:00';

function factsNow(): CarbonImmutable
{
    return CarbonImmutable::parse(FACTS_NOW, 'Europe/Berlin');
}

function factsArticle(ArticleKind $kind, string $text, ?int $id = null, array $attachments = []): TicketArticle
{
    return new TicketArticle($kind, 'Name', factsNow()->subDays(3), new HtmlString('<p>'.e($text).'</p>'), null, $attachments, null, false, null, $id);
}

/**
 * @param  list<TicketArticle>  $articles
 */
function factsTicket(array $articles, string $title = 'Betreff', bool $closed = false): Ticket
{
    return new Ticket('2137942', 1, $title, $closed ? 'closed' : 'open', $closed, null, null, 'Kunde', 'kunde@example.test', factsNow()->subDays(10), $articles, 'https://zammad.test/#ticket/zoom/1');
}

function factsGroup(string $key = 'reseller'): CustomerGroup
{
    return collect(CustomerGroup::all(config('knowledge.customer_groups')))->first(fn (CustomerGroup $group): bool => $group->key === $key);
}

/**
 * @param  list<EocsShipment>  $shipments
 * @param  list<EocsOrderItem>  $items
 * @param  list<EocsClaim>  $claims
 */
function factsOrder(string $number = '402-0000000-0000001', ?string $channel = 'amazon', ?CarbonImmutable $orderedAt = null, array $shipments = [], array $items = [], array $claims = [], int $id = 700001): EocsOrder
{
    return new EocsOrder($id, $number, 'Amazon', $channel, $orderedAt, 'Vollständig', 'green', null, $shipments, $items, $claims, 'https://eocs.test/orders/'.$id);
}

function factsShipment(?CarbonImmutable $shippedAt, bool $delivered = false): EocsShipment
{
    return new EocsShipment('DHL', 'Paket', 'T123', $shippedAt, $delivered);
}

function factsItem(int $quantity): EocsOrderItem
{
    return new EocsOrderItem('11281', 'Tasse', 'Tasse', $quantity, 'Vollständig', null, null, null);
}

function factsBuilder(): CaseFactsBuilder
{
    return app(CaseFactsBuilder::class);
}

describe('three-valued logic', function () {
    test('and is no as soon as a part is no, then unknown, otherwise yes', function () {
        expect(Tri::Yes->and(Tri::Yes))->toBe(Tri::Yes)
            ->and(Tri::Yes->and(Tri::Unknown))->toBe(Tri::Unknown)
            ->and(Tri::Unknown->and(Tri::No))->toBe(Tri::No)
            ->and(Tri::No->and(Tri::Unknown))->toBe(Tri::No)
            ->and(Tri::all([]))->toBe(Tri::Yes);
    });

    test('or is yes as soon as a part is yes, then unknown, otherwise no', function () {
        expect(Tri::No->or(Tri::No))->toBe(Tri::No)
            ->and(Tri::No->or(Tri::Unknown))->toBe(Tri::Unknown)
            ->and(Tri::Unknown->or(Tri::Yes))->toBe(Tri::Yes)
            ->and(Tri::Yes->or(Tri::Unknown))->toBe(Tri::Yes)
            ->and(Tri::any([]))->toBe(Tri::No);
    });

    test('not keeps unknown', function () {
        expect(Tri::Yes->not())->toBe(Tri::No)->and(Tri::No->not())->toBe(Tri::Yes)->and(Tri::Unknown->not())->toBe(Tri::Unknown)
            ->and(Tri::fromBool(null))->toBe(Tri::Unknown);
    });
});

describe('order facts', function () {
    test('without shipments, items and claims the facts are unknown, never no', function () {
        $facts = OrderFacts::fromEocs(factsOrder(orderedAt: factsNow()->subDays(12)), factsNow());

        expect($facts->get('order_age_days'))->toBe(12)->and($facts->get('order_channel'))->toBe('amazon')
            ->and($facts->get('shipped'))->toBeNull()->and($facts->get('shipment_count'))->toBeNull()->and($facts->get('days_since_shipped'))->toBeNull()
            ->and($facts->get('delivery_confirmed'))->toBeNull()->and($facts->get('all_shipments_delivered'))->toBeNull()
            ->and($facts->get('has_claim'))->toBeNull()->and($facts->get('claim_count'))->toBeNull()->and($facts->get('item_quantity_total'))->toBeNull();
    });

    test('a missing order date or channel is unknown', function () {
        $facts = OrderFacts::fromEocs(factsOrder(channel: null), factsNow());

        expect($facts->get('order_age_days'))->toBeNull()->and($facts->get('order_channel'))->toBeNull();
    });

    test('a single delivered shipment', function () {
        $facts = OrderFacts::fromEocs(factsOrder(shipments: [factsShipment(factsNow()->subDays(5), true)]), factsNow());

        expect($facts->get('shipped'))->toBeTrue()->and($facts->get('shipment_count'))->toBe(1)->and($facts->get('days_since_shipped'))->toBe(5)
            ->and($facts->get('days_since_last_shipped'))->toBe(5)->and($facts->get('delivery_confirmed'))->toBeTrue()->and($facts->get('all_shipments_delivered'))->toBeTrue();
    });

    test('partial deliveries: first and last shipment differ, delivery of one is not delivery of all', function () {
        $facts = OrderFacts::fromEocs(factsOrder(shipments: [factsShipment(factsNow()->subDays(20), true), factsShipment(factsNow()->subDays(4), false)]), factsNow());

        expect($facts->get('shipment_count'))->toBe(2)->and($facts->get('days_since_shipped'))->toBe(20)->and($facts->get('days_since_last_shipped'))->toBe(4)
            ->and($facts->get('delivery_confirmed'))->toBeTrue()->and($facts->get('all_shipments_delivered'))->toBeNull();
    });

    test('a shipment without a readable date still counts as shipped but has no day figures', function () {
        $facts = OrderFacts::fromEocs(factsOrder(shipments: [factsShipment(null)]), factsNow());

        expect($facts->get('shipped'))->toBeTrue()->and($facts->get('days_since_shipped'))->toBeNull()->and($facts->get('days_since_last_shipped'))->toBeNull();
    });

    test('claims, quantities', function () {
        $claim = new EocsClaim(1, 'R1-402-0000000-0000001', null, 'x', null, 'https://eocs.test/orders/1');
        $facts = OrderFacts::fromEocs(factsOrder(items: [factsItem(2), factsItem(3)], claims: [$claim, $claim]), factsNow());

        expect($facts->get('has_claim'))->toBeTrue()->and($facts->get('claim_count'))->toBe(2)->and($facts->get('item_quantity_total'))->toBe(5);
    });

    test('an order in the future counts as age zero', function () {
        expect(OrderFacts::fromEocs(factsOrder(orderedAt: factsNow()->addDays(2)), factsNow())->get('order_age_days'))->toBe(0);
    });
});

describe('case facts', function () {
    test('case-level facts', function () {
        $ticket = factsTicket([
            factsArticle(ArticleKind::Customer, 'Meine Tasse ist kaputt angekommen, bitte um Ersatz.', 11),
            factsArticle(ArticleKind::Agent, 'Wir schauen nach.'),
            factsArticle(ArticleKind::Customer, 'Hier ist das Foto.', 12, [['name' => 'foto.jpg']]),
        ], closed: true);

        $facts = factsBuilder()->build($ticket, factsGroup('reseller'), ['magic-mug'], [], [], [], null, factsNow());

        expect($facts->values)->toMatchArray([
            'customer_type' => 'b2b-reseller', 'sales_channel' => 'fachhaendler', 'products' => ['magic-mug'], 'ticket_closed' => true,
            'ticket_age_days' => 10, 'customer_message_count' => 2, 'current_message_has_attachment' => true,
        ]);
    });

    test('an unclear customer group makes type and channel unknown; no customer message makes message facts unknown', function () {
        $facts = factsBuilder()->build(factsTicket([factsArticle(ArticleKind::Agent, 'Hallo')]), factsGroup('unclear'), [], [], [], [], null, factsNow());

        expect($facts->get('customer_type'))->toBeNull()->and($facts->get('sales_channel'))->toBeNull()->and($facts->get('products'))->toBe([])
            ->and($facts->get('current_message_has_attachment'))->toBeNull()->and($facts->get('message_foreign'))->toBeNull()->and($facts->get('customer_message_count'))->toBe(0);
    });

    test('the language is a heuristic and unknown for very short texts', function () {
        $german = factsTicket([factsArticle(ArticleKind::Customer, 'Guten Tag, ich habe die Tasse nicht erhalten und möchte wissen, wann sie bei mir ankommt. Das ist eine Frage von mir.')]);
        $italian = factsTicket([factsArticle(ArticleKind::Customer, 'Buongiorno, ho ricevuto il prodotto ma non è quello che ho ordinato, vorrei sapere come posso fare per il rimborso. Grazie mille.')]);
        $short = factsTicket([factsArticle(ArticleKind::Customer, 'Hi')]);

        expect(factsBuilder()->build($german, factsGroup(), [], [])->get('message_foreign'))->toBeFalse()
            ->and(factsBuilder()->build($italian, factsGroup(), [], [])->get('message_foreign'))->toBeTrue()
            ->and(factsBuilder()->build($short, factsGroup(), [], [])->get('message_foreign'))->toBeNull();
    });
});

describe('text tiers', function () {
    test('the last customer message is the current concern, earlier ones are recent or the rest; our replies and notes do not count', function () {
        $articles = [];

        foreach (range(1, 6) as $number) {
            $articles[] = factsArticle(ArticleKind::Customer, "Kundennachricht {$number} mit genug Text, damit sie nicht kurz ist.", $number);
            $articles[] = factsArticle(ArticleKind::Agent, "Unsere Antwort {$number}.");
            $articles[] = factsArticle(ArticleKind::Internal, "Interne Notiz {$number}.");
        }

        $tiers = factsBuilder()->build(factsTicket($articles, 'Betreff alt'), factsGroup(), [], [])->tiers;

        expect($tiers['A'])->toBe(['Kundennachricht 6 mit genug Text, damit sie nicht kurz ist.'])
            ->and($tiers['B'])->toBe(['Kundennachricht 5 mit genug Text, damit sie nicht kurz ist.', 'Kundennachricht 4 mit genug Text, damit sie nicht kurz ist.', 'Kundennachricht 3 mit genug Text, damit sie nicht kurz ist.'])
            ->and($tiers['C'])->toBe(['Kundennachricht 2 mit genug Text, damit sie nicht kurz ist.', 'Kundennachricht 1 mit genug Text, damit sie nicht kurz ist.', 'Betreff alt'])
            ->and(implode(' ', [...$tiers['A'], ...$tiers['B'], ...$tiers['C']]))->not->toContain('Antwort')->not->toContain('Notiz');
    });

    test('a very short last message also counts the one before it', function () {
        $ticket = factsTicket([
            factsArticle(ArticleKind::Customer, 'Die Tasse hat einen Riss am Henkel, das Motiv ist auch verschoben und ich möchte Ersatz.', 1),
            factsArticle(ArticleKind::Agent, 'Wir schicken Ersatz.'),
            factsArticle(ArticleKind::Customer, 'ok danke', 2),
        ], 'Reklamation');

        $tiers = factsBuilder()->build($ticket, factsGroup(), [], [])->tiers;

        expect($tiers['A'])->toBe(['Reklamation', 'ok danke', 'Die Tasse hat einen Riss am Henkel, das Motiv ist auch verschoben und ich möchte Ersatz.']);
    });

    test('the title belongs to the current concern in a young ticket and to the rest in an older one', function () {
        $young = factsTicket([factsArticle(ArticleKind::Customer, 'Eine ausführliche erste Nachricht mit mehr als vierzig Zeichen Länge.', 1)], 'Titel jung');
        $old = factsTicket([
            factsArticle(ArticleKind::Customer, 'Eine ausführliche erste Nachricht mit mehr als vierzig Zeichen Länge.', 1),
            factsArticle(ArticleKind::Customer, 'Eine ausführliche zweite Nachricht mit mehr als vierzig Zeichen Länge.', 2),
            factsArticle(ArticleKind::Customer, 'Eine ausführliche dritte Nachricht mit mehr als vierzig Zeichen Länge.', 3),
        ], 'Titel alt');

        expect(factsBuilder()->build($young, factsGroup(), [], [])->tiers['A'])->toContain('Titel jung')
            ->and(factsBuilder()->build($old, factsGroup(), [], [])->tiers['A'])->not->toContain('Titel alt')
            ->and(factsBuilder()->build($old, factsGroup(), [], [])->tiers['C'])->toContain('Titel alt');
    });

    test('a German translation is searched together with the original; the summary counts as recent', function () {
        $ticket = factsTicket([factsArticle(ArticleKind::Customer, 'Buongiorno, la tazza è rotta, vorrei un rimborso per favore.', 7)]);

        $tiers = factsBuilder()->build($ticket, factsGroup(), [], [], [], [7 => ['text' => 'Guten Tag, die Tasse ist kaputt.']], 'Der Kunde wünscht eine Erstattung.')->tiers;

        expect($tiers['A'][1])->toContain('Buongiorno')->toContain('Guten Tag, die Tasse ist kaputt.')
            ->and($tiers['B'])->toBe(['Der Kunde wünscht eine Erstattung.']);
    });
});

describe('what the employee and the order add to the texts', function () {
    test('the note of the employee counts as the current concern', function () {
        $ticket = factsTicket([factsArticle(ArticleKind::Customer, 'Ich habe ein Foto hochgeladen, bitte so drucken.', 1)]);

        $tiers = factsBuilder()->build($ticket, factsGroup(), [], [], [], [], null, factsNow(), 'Das Foto zeigt ein Hakenkreuz.')->tiers;

        expect($tiers['A'])->toContain('Das Foto zeigt ein Hakenkreuz.');
    });

    test('an empty note adds nothing', function () {
        $ticket = factsTicket([factsArticle(ArticleKind::Customer, 'Eine ausführliche Nachricht mit genug Text für den Test hier.', 1)]);

        expect(factsBuilder()->build($ticket, factsGroup(), [], [], [], [], null, factsNow(), '   ')->tiers['A'])->toBe(['Betreff', 'Eine ausführliche Nachricht mit genug Text für den Test hier.']);
    });

    test('texts the customer entered for the products of the order join the recent texts, only for the orders the concern refers to', function () {
        $ticket = factsTicket([factsArticle(ArticleKind::Customer, 'Zur Bestellung 402-0000000-0000002 habe ich eine Frage zu meiner Tasse, bitte melden Sie sich.', 1)]);
        $item = fn (string $text): EocsOrderItem => new EocsOrderItem('1', 'Tasse', 'Tasse', 1, null, null, null, null, ['customizationData' => ['text' => $text], 'customizationInfo' => ['Zeile 1', ['Zeile 2']]]);
        $orders = [
            factsOrder('402-0000000-0000001', items: [$item('Text der anderen Bestellung')], id: 1),
            factsOrder('402-0000000-0000002', items: [$item('Hallo Welt')], id: 2),
        ];

        $tiers = factsBuilder()->build($ticket, factsGroup(), [], $orders, [], [], null, factsNow())->tiers;

        expect($tiers['B'])->toBe(['Hallo Welt', 'Zeile 1', 'Zeile 2']);
    });
});

describe('the order the concern refers to', function () {
    test('one named order number decides, the other loaded order is not a candidate', function () {
        $ticket = factsTicket([factsArticle(ArticleKind::Customer, 'Zur Bestellung 402-0000000-0000002 habe ich ein Problem, die Tasse ist nicht angekommen.', 1)]);
        $orders = [factsOrder('402-0000000-0000001', id: 1), factsOrder('402-0000000-0000002', id: 2)];

        $facts = factsBuilder()->build($ticket, factsGroup(), [], $orders, [], [], null, factsNow());

        expect($facts->reference)->toBe(CaseFacts::REFERENCE_NAMED)->and(array_map(fn (OrderFacts $order): ?string => $order->number, $facts->orders))->toBe(['402-0000000-0000002']);
    });

    test('a named order that was not loaded stays unknown and no other order stands in for it', function () {
        $ticket = factsTicket([factsArticle(ArticleKind::Customer, 'Zur Bestellung 402-0000000-0000009 habe ich ein Problem, die Tasse ist nicht angekommen.', 1)]);
        $mentioned = [['number' => '402-0000000-0000009', 'channel' => 'amazon', 'items' => []]];

        $facts = factsBuilder()->build($ticket, factsGroup(), [], [factsOrder('402-0000000-0000001', shipments: [factsShipment(factsNow()->subDays(30))])], $mentioned, [], null, factsNow());

        expect($facts->reference)->toBe(CaseFacts::REFERENCE_NAMED)->and($facts->orders)->toHaveCount(1)
            ->and($facts->orders[0]->number)->toBe('402-0000000-0000009')->and($facts->orders[0]->get('shipped'))->toBeNull();
    });

    test('without a named order a single known order is the reference', function () {
        $ticket = factsTicket([factsArticle(ArticleKind::Customer, 'Meine Tasse ist nicht angekommen, bitte um Hilfe bei dieser Sache.', 1)]);

        expect(factsBuilder()->build($ticket, factsGroup(), [], [factsOrder()])->reference)->toBe(CaseFacts::REFERENCE_SINGLE);
    });

    test('several orders without a name are all candidates and the reference is unclear', function () {
        $ticket = factsTicket([factsArticle(ArticleKind::Customer, 'Meine Tasse ist nicht angekommen, bitte um Hilfe bei dieser Sache.', 1)]);

        $facts = factsBuilder()->build($ticket, factsGroup(), [], [factsOrder('402-0000000-0000001', id: 1), factsOrder('402-0000000-0000002', id: 2)]);

        expect($facts->reference)->toBe(CaseFacts::REFERENCE_UNCLEAR)->and($facts->orders)->toHaveCount(2)->and($facts->orderReferenceIsUnclear())->toBeTrue();
    });

    test('an order number in an older message does not decide', function () {
        $ticket = factsTicket([
            factsArticle(ArticleKind::Customer, 'Bestellung 402-0000000-0000001 ist nicht angekommen, das ist leider schon der zweite Versuch.', 1),
            factsArticle(ArticleKind::Customer, 'Und bei der anderen Bestellung ist eine weitere Tasse mit einem Riss angekommen, bitte um Hilfe.', 2),
        ]);

        $facts = factsBuilder()->build($ticket, factsGroup(), [], [factsOrder('402-0000000-0000001', id: 1), factsOrder('402-0000000-0000002', id: 2)]);

        expect($facts->reference)->toBe(CaseFacts::REFERENCE_UNCLEAR)->and($facts->orders)->toHaveCount(2);
    });

    test('no order at all', function () {
        $ticket = factsTicket([factsArticle(ArticleKind::Customer, 'Meine Tasse ist nicht angekommen, bitte um Hilfe bei dieser Sache.', 1)]);

        $facts = factsBuilder()->build($ticket, factsGroup(), [], []);

        expect($facts->reference)->toBe(CaseFacts::REFERENCE_NONE)->and($facts->orders)->toBe([]);
    });

    test('an order number that is neither loaded nor known from the ticket is ignored', function () {
        $ticket = factsTicket([factsArticle(ArticleKind::Customer, 'Bei 300-0000000-0000007 habe ich ein Problem, die Tasse ist nicht angekommen, bitte helft.', 1)]);

        expect(factsBuilder()->build($ticket, factsGroup(), [], [factsOrder()])->reference)->toBe(CaseFacts::REFERENCE_SINGLE);
    });
});

function evaluate(array $when, CaseFacts $facts): Evaluation
{
    return (new WhenEvaluator(config('knowledge.retrieval.when_keys')))->evaluate($when, $facts);
}

function plainFacts(array $values = [], array $orders = [], string $reference = CaseFacts::REFERENCE_NONE): CaseFacts
{
    return new CaseFacts($values, $orders, $reference, ['A' => [], 'B' => [], 'C' => []]);
}

describe('evaluating a condition', function () {
    test('case-level keys of every type', function () {
        $facts = plainFacts(['customer_type' => 'b2c', 'sales_channel' => 'amazon', 'products' => ['magic-mug'], 'ticket_closed' => false, 'ticket_age_days' => 10, 'customer_message_count' => 3, 'message_foreign' => true]);

        expect(evaluate(['customer_type' => ['b2c', 'b2b-pro']], $facts)->result)->toBe(Tri::Yes)
            ->and(evaluate(['sales_channel' => 'looxis-de'], $facts)->result)->toBe(Tri::No)
            ->and(evaluate(['products' => ['magic-mug', 'lunchbox']], $facts)->result)->toBe(Tri::Yes)
            ->and(evaluate(['products' => ['lunchbox']], $facts)->result)->toBe(Tri::No)
            ->and(evaluate(['ticket_closed' => false], $facts)->result)->toBe(Tri::Yes)
            ->and(evaluate(['ticket_closed' => true], $facts)->result)->toBe(Tri::No)
            ->and(evaluate(['ticket_age_days' => ['min' => 7]], $facts)->result)->toBe(Tri::Yes)
            ->and(evaluate(['ticket_age_days' => ['max' => 9]], $facts)->result)->toBe(Tri::No)
            ->and(evaluate(['ticket_age_days' => ['min' => 10, 'max' => 10]], $facts)->result)->toBe(Tri::Yes)
            ->and(evaluate(['customer_message_count' => 3], $facts)->result)->toBe(Tri::Yes)
            ->and(evaluate(['message_foreign' => true], $facts)->result)->toBe(Tri::Yes);
    });

    test('an empty product list is a known no, an unknown fact is unknown', function () {
        $facts = plainFacts(['customer_type' => null, 'products' => [], 'message_foreign' => null]);

        expect(evaluate(['products' => ['magic-mug']], $facts)->result)->toBe(Tri::No)
            ->and(evaluate(['customer_type' => ['b2c']], $facts)->result)->toBe(Tri::Unknown)
            ->and(evaluate(['message_foreign' => true], $facts)->result)->toBe(Tri::Unknown);
    });

    test('and over all keys, with no beating unknown', function () {
        $facts = plainFacts(['customer_type' => null, 'ticket_closed' => true, 'customer_message_count' => 1]);

        expect(evaluate(['customer_type' => ['b2c'], 'ticket_closed' => false], $facts)->result)->toBe(Tri::No)
            ->and(evaluate(['customer_type' => ['b2c'], 'ticket_closed' => true], $facts)->result)->toBe(Tri::Unknown)
            ->and(evaluate(['ticket_closed' => true, 'customer_message_count' => 1], $facts)->result)->toBe(Tri::Yes);
    });

    test('any_of and not', function () {
        $facts = plainFacts(['customer_type' => null, 'ticket_closed' => true]);

        expect(evaluate(['any_of' => [['ticket_closed' => true], ['customer_type' => 'b2c']]], $facts)->result)->toBe(Tri::Yes)
            ->and(evaluate(['any_of' => [['ticket_closed' => false], ['customer_type' => 'b2c']]], $facts)->result)->toBe(Tri::Unknown)
            ->and(evaluate(['any_of' => [['ticket_closed' => false], ['ticket_closed' => false, 'customer_type' => 'b2c']]], $facts)->result)->toBe(Tri::No)
            ->and(evaluate(['not' => ['ticket_closed' => true]], $facts)->result)->toBe(Tri::No)
            ->and(evaluate(['not' => ['customer_type' => 'b2c']], $facts)->result)->toBe(Tri::Unknown);
    });

    test('the checks name key, fact and result for the log', function () {
        $evaluation = evaluate(['ticket_closed' => false, 'customer_type' => ['b2c']], plainFacts(['ticket_closed' => true, 'customer_type' => null]));

        expect($evaluation->checks)->toBe([
            ['key' => 'ticket_closed', 'fact' => 'ja', 'result' => 'no', 'order' => null],
            ['key' => 'customer_type', 'fact' => 'unbekannt', 'result' => 'unknown', 'order' => null],
        ])->and($evaluation->unknownKeys())->toBe(['customer_type']);
    });
});

describe('order conditions never mix orders', function () {
    function orderCandidate(string $number, array $values): OrderFacts
    {
        return new OrderFacts($number, $values);
    }

    test('all order conditions must hold for the same order', function () {
        $a = orderCandidate('A', ['order_channel' => 'amazon', 'days_since_shipped' => 3]);
        $b = orderCandidate('B', ['order_channel' => 'looxis-de', 'days_since_shipped' => 30]);
        $when = ['order_channel' => 'amazon', 'days_since_shipped' => ['min' => 14]];

        $evaluation = evaluate($when, plainFacts([], [$a, $b], CaseFacts::REFERENCE_UNCLEAR));

        expect($evaluation->result)->toBe(Tri::No)->and($evaluation->orderUnclear)->toBeTrue();
    });

    test('one order satisfying everything is enough and is named in the log', function () {
        $a = orderCandidate('A', ['order_channel' => 'amazon', 'days_since_shipped' => 3]);
        $b = orderCandidate('B', ['order_channel' => 'amazon', 'days_since_shipped' => 30]);

        $evaluation = evaluate(['order_channel' => 'amazon', 'days_since_shipped' => ['min' => 14]], plainFacts([], [$a, $b], CaseFacts::REFERENCE_UNCLEAR));

        expect($evaluation->result)->toBe(Tri::Yes)->and($evaluation->order)->toBe('B')->and($evaluation->orderUnclear)->toBeTrue()
            ->and(array_unique(array_column($evaluation->checks, 'order')))->toBe(['B']);
    });

    test('unknown if no order satisfies it but one cannot be told; a known no beats an unknown within one order', function () {
        $known = orderCandidate('A', ['order_channel' => 'looxis-de']);
        $partly = orderCandidate('B', ['order_channel' => 'amazon']);

        expect(evaluate(['order_channel' => 'amazon', 'days_since_shipped' => ['min' => 14]], plainFacts([], [$known, $partly], CaseFacts::REFERENCE_UNCLEAR))->result)->toBe(Tri::Unknown)
            ->and(evaluate(['order_channel' => 'amazon', 'days_since_shipped' => ['min' => 14]], plainFacts([], [$known], CaseFacts::REFERENCE_SINGLE))->result)->toBe(Tri::No);
    });

    test('a named order that was not loaded makes the order conditions unknown', function () {
        $evaluation = evaluate(['days_since_shipped' => ['min' => 14]], plainFacts([], [OrderFacts::unknown('402-0000000-0000009')], CaseFacts::REFERENCE_NAMED));

        expect($evaluation->result)->toBe(Tri::Unknown)->and($evaluation->order)->toBe('402-0000000-0000009')->and($evaluation->orderUnclear)->toBeFalse();
    });

    test('without any order the order conditions are unknown, unless a case condition already fails', function () {
        expect(evaluate(['has_claim' => true], plainFacts())->result)->toBe(Tri::Unknown)
            ->and(evaluate(['has_claim' => true, 'ticket_closed' => false], plainFacts(['ticket_closed' => true]))->result)->toBe(Tri::No);
    });

    test('not and any_of use the same order for all their parts', function () {
        $a = orderCandidate('A', ['shipped' => true, 'has_claim' => true]);
        $b = orderCandidate('B', ['shipped' => null, 'has_claim' => null]);

        $when = ['shipped' => true, 'not' => ['has_claim' => true]];

        expect(evaluate($when, plainFacts([], [$a], CaseFacts::REFERENCE_SINGLE))->result)->toBe(Tri::No)
            ->and(evaluate($when, plainFacts([], [$a, $b], CaseFacts::REFERENCE_UNCLEAR))->result)->toBe(Tri::Unknown);
    });

    test('a case-level condition counts once for every candidate', function () {
        $a = orderCandidate('A', ['shipped' => true]);

        expect(evaluate(['ticket_closed' => true, 'shipped' => true], plainFacts(['ticket_closed' => false], [$a, $a], CaseFacts::REFERENCE_UNCLEAR))->result)->toBe(Tri::No);
    });

    test('numbers: equal value, bounds inclusive', function () {
        $a = orderCandidate('A', ['shipment_count' => 2, 'order_age_days' => 14]);
        $facts = plainFacts([], [$a], CaseFacts::REFERENCE_SINGLE);

        expect(evaluate(['shipment_count' => 2], $facts)->result)->toBe(Tri::Yes)
            ->and(evaluate(['shipment_count' => 1], $facts)->result)->toBe(Tri::No)
            ->and(evaluate(['order_age_days' => ['min' => 14, 'max' => 14]], $facts)->result)->toBe(Tri::Yes);
    });
});
