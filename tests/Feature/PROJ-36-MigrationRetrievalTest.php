<?php

use App\Knowledge\CaseContext;
use App\Knowledge\CustomerGroup;
use App\Knowledge\Retrieval\CaseFactsBuilder;
use App\Knowledge\Retrieval\KnowledgeRetriever;
use App\Knowledge\Retrieval\RetrievalResult;
use App\Zammad\ArticleKind;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use App\Zammad\TicketAttachment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Symfony\Component\Yaml\Yaml;

/*
| The proposed retrieval metadata for the 75 real documents (tests/Fixtures/retrieval-migration-proposal.json)
| is laid over a copy of the real knowledge base. The files in knowledge/ stay untouched until the
| proposal is approved. Positive and negative cases use synthetic customer requests.
*/

afterEach(fn () => cleanUpKnowledgeBases());

/**
 * IDs retrieved for a synthetic request.
 *
 * @param  list<string>  $products
 * @return list<string>
 */
function migrationRetrieve(string $group, string $text, array $products = [], string $title = '', string $employeeContext = '', bool $attachment = false): array
{
    return migrationResult($group, $text, $products, $title, $employeeContext, $attachment)->ids();
}

/**
 * The real documents with the proposed metadata applied, as a throwaway knowledge base, and the retrieval over them.
 *
 * @param  list<string>  $products
 */
function migrationResult(string $group, string $text, array $products = [], string $title = '', string $employeeContext = '', bool $attachment = false): RetrievalResult
{
    $proposal = json_decode((string) file_get_contents(base_path('tests/Fixtures/retrieval-migration-proposal.json')), true, flags: JSON_THROW_ON_ERROR);
    $files = [];

    foreach (File::allFiles(base_path('knowledge')) as $file) {
        $relative = $file->getRelativePathname();
        if (! str_ends_with($relative, '.md') || str_starts_with($relative, 'templates/') || $relative === 'README.md') {
            continue;
        }
        $raw = (string) file_get_contents($file->getPathname());
        if (! preg_match('/\A---\n(.*?\n)---\n(.*)\z/s', $raw, $parts)) {
            continue;
        }
        $front = Yaml::parse($parts[1]);
        if (isset($proposal[$front['id'] ?? ''])) {
            $front = array_merge($front, array_filter($proposal[$front['id']], fn ($value, $key) => $key !== 'when' || $value !== null, ARRAY_FILTER_USE_BOTH));
            if (($proposal[$front['id']]['when'] ?? null) === null) {
                unset($front['when']);
            }
        }
        $files[$relative] = "---\n".Yaml::dump($front, 4, 2)."---\n".$parts[2];
    }

    knowledgeBase($files);

    $groupObject = collect(CustomerGroup::all(config('knowledge.customer_groups')))->first(fn (CustomerGroup $item): bool => $item->key === $group);
    $context = new CaseContext($groupObject, $products);
    $article = new TicketArticle(ArticleKind::Customer, 'Kunde', CarbonImmutable::now(), new HtmlString(nl2br(e($text))), attachments: $attachment ? [new TicketAttachment('foto.jpg', 'image/jpeg', 1000)] : []);
    $ticket = new Ticket('0', 0, $title, 'open', false, null, null, null, null, CarbonImmutable::now(), [$article], '');
    $facts = app(CaseFactsBuilder::class)->build($ticket, $groupObject, $products, [], employeeContext: $employeeContext);

    return app(KnowledgeRetriever::class)->retrieve($context, $facts);
}

test('migration: a 3D glass photo that is too dark gets the glass documents, not the print colour documents', function () {
    $ids = migrationRetrieve('private-looxis-de', 'Das 3D-Glasfoto ist viel zu dunkel, man erkennt den Anzug kaum.', ['3d-glass-photo']);

    expect($ids)->toContain('GLOSSARY-004', 'PLAYBOOK-009')
        ->and($ids)->not->toContain('GLOSSARY-001', 'PLAYBOOK-001', 'PROCESS-002');
});

test('migration: a printed mug that is too dark still gets the colour documents', function () {
    $ids = migrationRetrieve('private-looxis-de', 'Die Farben auf der Tasse sind viel zu dunkel und es gibt einen Gelbstich.', ['magic-mug']);

    expect($ids)->toContain('PLAYBOOK-001', 'GLOSSARY-001', 'PROCESS-002');
});

test('migration: shadow gaps in a 3D glass photo retrieve the shadow policy, a general goodwill question does not', function () {
    $shadow = migrationRetrieve('private-looxis-de', 'Beim 3D-Glasfoto fehlt ein Stück vom Arm, da ist eine Lücke durch den Schatten.', ['3d-glass-photo']);
    $goodwill = migrationRetrieve('private-looxis-de', 'Ich hätte gern Kulanz, weil mir das Glasfoto nicht gefällt.', ['3d-glass-photo']);

    expect($shadow)->toContain('POLICY-016', 'PLAYBOOK-008')
        ->and($goodwill)->not->toContain('POLICY-016');
});

test('migration: three customer messages alone do not retrieve the repetition documents; an explicit repetition does', function () {
    $plain = migrationRetrieve('private-amazon', "Wann kommt mein Paket?\n\nIch habe eine Frage zur Größe.\n\nDanke für die Auskunft.");
    $repeat = migrationRetrieve('private-amazon', 'Ich schreibe Ihnen zum wiederholten Mal wegen derselben Sache.');

    expect($plain)->not->toContain('PROCESS-003', 'TONE-003')
        ->and($repeat)->toContain('PROCESS-003', 'TONE-003');
});

test('migration: a photo attachment alone does not retrieve the content policy; text or employee note does', function () {
    $photo = migrationRetrieve('private-looxis-de', 'Anbei das Foto der kaputten Tasse, die Ecke ist abgesplittert.', ['magic-mug'], attachment: true);
    $text = migrationRetrieve('private-looxis-de', 'Mein Motiv wurde abgelehnt, warum?');
    $note = migrationRetrieve('private-looxis-de', 'Bitte um Druck des angehängten Fotos.', employeeContext: 'Motiv prüfen: Bild zeigt eventuell ein Hakenkreuz.', attachment: true);

    expect($photo)->not->toContain('POLICY-018')
        ->and($text)->toContain('POLICY-018')
        ->and($note)->toContain('POLICY-018');
});

test('migration: a rights question retrieves the rights documents, a quote with the own company logo does not', function () {
    $question = migrationRetrieve('private-looxis-de', 'Darf ich das Logo von Adidas auf eine Tasse drucken? Ist das urheberrechtlich geschützt?');
    $quote = migrationRetrieve('private-looxis-de', 'Wir hätten gern ein Angebot für 200 Tassen mit unserem Firmenlogo für eine Firmenveranstaltung.');

    expect($question)->toContain('POLICY-017', 'PLAYBOOK-014')
        ->and($quote)->not->toContain('POLICY-017', 'PLAYBOOK-014', 'TONE-007');
});

test('migration: a quote asks for the quote rules; the Christmas rule stays with quotes until the document is split', function () {
    $ids = migrationRetrieve('private-looxis-de', 'Wir brauchen 150 Stück für eine Firmenfeier, bitte um ein Angebot.');

    expect($ids)->toContain('POLICY-024', 'POLICY-025', 'POLICY-026', 'PROCESS-010', 'TONE-009', 'POLICY-023');
});

test('migration: a quote request from a reseller does not get the looxis.de quantity rules', function () {
    $ids = migrationRetrieve('reseller', 'Wir brauchen 150 Stück, bitte um ein Angebot.');

    expect($ids)->not->toContain('POLICY-024', 'POLICY-025', 'POLICY-026', 'PROCESS-010');
});

test('migration: a general complaint of a private customer does not pull the reproduction process', function () {
    $ids = migrationRetrieve('private-looxis-de', 'Die Tasse ist beschädigt angekommen, ich möchte Ersatz.', ['magic-mug']);
    $again = migrationRetrieve('private-looxis-de', 'Bitte produzieren Sie das Glasfoto neu, mit der gleichen Interpretation wie vorher.', ['3d-glass-photo']);

    expect($ids)->not->toContain('PROCESS-001')
        ->and($again)->toContain('PROCESS-001');
});

test('migration: invoice request versus invoice change versus missing delivery', function () {
    $request = migrationRetrieve('private-looxis-de', 'Ich habe meine Rechnung nicht erhalten, bitte noch einmal senden.');
    $change = migrationRetrieve('private-looxis-de', 'Bitte ändern Sie die Rechnungsadresse auf unsere Firma.');
    $delivery = migrationRetrieve('private-looxis-de', 'Wo bleibt meine Bestellung? Das Paket ist noch nicht angekommen.');

    expect($request)->toContain('POLICY-019')->and($request)->not->toContain('POLICY-020', 'POLICY-003')
        ->and($change)->toContain('POLICY-020')->and($change)->not->toContain('POLICY-019')
        ->and($delivery)->toContain('POLICY-003')->and($delivery)->not->toContain('POLICY-019', 'POLICY-020');
});

test('migration: an invoice before prepayment at a reseller retrieves the prepayment playbook', function () {
    $ids = migrationRetrieve('reseller', 'Wir benötigen vor der Vorauszahlung eine Rechnung.');

    expect($ids)->toContain('PLAYBOOK-016');
});

test('migration: an unrelated greeting retrieves only the core and no triggered documents', function () {
    $ids = migrationRetrieve('private-looxis-de', 'Guten Tag, vielen Dank für Ihre schnelle Hilfe!');

    expect($ids)->toContain('POLICY-004', 'TONE-002')
        ->and($ids)->not->toContain('POLICY-017', 'POLICY-018', 'POLICY-016', 'PROCESS-001', 'TONE-003', 'PROCESS-003');
});

/**
 * IDs the action safeguard would load after the analysis recommended the actions.
 *
 * @param  list<string>  $actions
 * @param  list<string>  $products
 * @return list<string>
 */
function migrationSafeguard(string $group, string $text, array $actions, array $products = []): array
{
    return array_map(
        fn ($document): string => (string) $document->id,
        migrationResult($group, $text, $products)->safeguardDocuments($actions),
    );
}

describe('action safeguard for binding documents', function () {
    $complaint = 'Die Tasse ist beschädigt angekommen, der Henkel ist gesprungen.';

    test('a justified complaint with a free reproduction loads the authority but not the customer-error goodwill rules', function () use ($complaint) {
        $shop = migrationSafeguard('private-looxis-de', $complaint, ['reproduction'], ['magic-mug']);
        $amazon = migrationSafeguard('private-amazon', $complaint, ['reproduction'], ['magic-mug']);
        $reseller = migrationSafeguard('reseller', $complaint, ['reproduction'], ['magic-mug']);

        expect($shop)->toContain('PERMISSION-002')->and($shop)->not->toContain('POLICY-005', 'POLICY-006', 'POLICY-007', 'POLICY-008')
            ->and($amazon)->toContain('PERMISSION-002')->and($amazon)->not->toContain('POLICY-006', 'POLICY-009')
            ->and($reseller)->toContain('PERMISSION-002')->and($reseller)->not->toContain('POLICY-008');
    });

    test('a free reproduction loads neither the customer-error goodwill rules nor the discount and payment rules', function () use ($complaint) {
        foreach (['private-looxis-de', 'private-amazon', 'reseller'] as $group) {
            expect(migrationSafeguard($group, $complaint, ['reproduction'], ['magic-mug']))
                ->not->toContain('POLICY-005', 'POLICY-006', 'POLICY-007', 'POLICY-008', 'POLICY-009', 'PERMISSION-003');
        }
    });

    test('a paid goodwill reproduction loads the goodwill rules of the customer group', function () use ($complaint) {
        $shop = migrationSafeguard('private-looxis-de', $complaint, ['goodwill-reproduction'], ['magic-mug']);
        $amazon = migrationSafeguard('private-amazon', $complaint, ['goodwill-reproduction'], ['magic-mug']);
        $reseller = migrationSafeguard('reseller', $complaint, ['goodwill-reproduction'], ['magic-mug']);

        expect($shop)->toContain('POLICY-005', 'POLICY-007')->and($shop)->not->toContain('POLICY-006', 'PERMISSION-003', 'PERMISSION-002')
            ->and($amazon)->toContain('POLICY-006', 'POLICY-007', 'POLICY-009', 'PERMISSION-003')->and($amazon)->not->toContain('POLICY-005')
            ->and($reseller)->toContain('POLICY-008')->and($reseller)->not->toContain('POLICY-005', 'POLICY-007');
    });

    test('a partial refund alone loads the discount and payment rules, not the reproduction goodwill rules', function () use ($complaint) {
        $shop = migrationSafeguard('private-looxis-de', $complaint, ['partial-refund'], ['magic-mug']);
        $amazon = migrationSafeguard('private-amazon', $complaint, ['partial-refund'], ['magic-mug']);

        expect($shop)->toContain('POLICY-007')->and($shop)->not->toContain('POLICY-005', 'POLICY-006', 'POLICY-008')
            ->and($amazon)->toContain('POLICY-009', 'PERMISSION-003')->and($amazon)->not->toContain('POLICY-006');
    });

    test('a regular refund loads the refund authority, neither the Amazon goodwill authority nor the no-return rule', function () use ($complaint) {
        $ids = migrationSafeguard('private-amazon', $complaint, ['refund'], ['magic-mug']);

        expect($ids)->toContain('PERMISSION-001')->and($ids)->not->toContain('PERMISSION-003', 'POLICY-001');
    });

    test('a return or cancellation loads the no-return rule', function () use ($complaint) {
        expect(migrationSafeguard('private-looxis-de', $complaint, ['return']))->toContain('POLICY-001')
            ->and(migrationSafeguard('private-looxis-de', $complaint, ['cancellation']))->toContain('POLICY-001');
    });

    test('a refund request by the customer is not read as a wish to return the goods', function () {
        $ids = migrationRetrieve('private-looxis-de', 'Die Lieferung ist defekt, ich möchte mein Geld zurück.');

        expect($ids)->toContain('PERMISSION-001')->and($ids)->not->toContain('POLICY-001');
    });

    test('an explicit wish to return because of dislike loads the no-return rule at once', function () {
        expect(migrationRetrieve('private-looxis-de', 'Das Motiv gefällt mir nicht, ich möchte es zurückgeben.'))->toContain('POLICY-001');
    });

    test('a new 3D interpretation approval comes with a reproduction only for 3D glass photos', function () use ($complaint) {
        expect(migrationSafeguard('private-looxis-de', $complaint, ['reproduction'], ['3d-glass-photo']))->toContain('PROCESS-007')
            ->and(migrationSafeguard('private-looxis-de', $complaint, ['reproduction'], ['magic-mug']))->not->toContain('PROCESS-007');
    });

    test('the quote and order rules wait for their actions', function () {
        $text = 'Guten Tag, ich habe eine Frage zum Produkt.';

        expect(migrationSafeguard('private-looxis-de', $text, ['quote']))->toContain('POLICY-024', 'POLICY-025', 'POLICY-026')
            ->and(migrationSafeguard('private-looxis-de', $text, ['order-confirmation']))->toContain('PROCESS-011')
            ->and(migrationSafeguard('private-looxis-de', $text, ['reproduction']))->not->toContain('PROCESS-011', 'POLICY-024');
    });
});

test('migration: an unclear damage to the lunchbox is not read as a broken snap fastener', function () {
    $unclear = migrationRetrieve('private-looxis-de', 'Ein Stück meiner Lunchbox ist abgebrochen.', ['lunchbox-wood-lid']);
    $fastener = migrationRetrieve('private-looxis-de', 'Der Schnappverschluss meiner Lunchbox ist gebrochen.', ['lunchbox-wood-lid']);
    $closure = migrationRetrieve('private-looxis-de', 'Der Verschluss ist abgebrochen.', ['lunchbox-wood-lid']);

    expect($unclear)->toContain('PRODUCT-005')->and($unclear)->not->toContain('PLAYBOOK-013')
        ->and($fastener)->toContain('PLAYBOOK-013')
        ->and($closure)->toContain('PLAYBOOK-013');
});

test('migration: the colour documents skip glass photos by product, but not an unknown or another product', function () {
    $text = 'Die Farben sind zu dunkel und es gibt einen Farbstich.';
    $glass = migrationRetrieve('private-looxis-de', $text, ['3d-glass-photo']);
    $both = migrationRetrieve('private-looxis-de', $text, ['3d-glass-photo', 'magic-mug']);
    $mug = migrationRetrieve('private-looxis-de', $text, ['magic-mug']);
    $unknown = migrationRetrieve('private-looxis-de', $text);

    expect($glass)->not->toContain('PLAYBOOK-001', 'PROCESS-002', 'GLOSSARY-001')
        ->and($both)->toContain('PLAYBOOK-001', 'GLOSSARY-001')
        ->and($mug)->toContain('PLAYBOOK-001', 'PROCESS-002', 'GLOSSARY-001')
        ->and($unknown)->toContain('PLAYBOOK-001', 'PROCESS-002', 'GLOSSARY-001');
});

test('the analysis prompt names the goodwill reproduction apart from the free one', function () {
    $actions = config('knowledge.actions');
    $hints = config('knowledge.action_hints');

    expect($actions)->toHaveKeys(['reproduction', 'goodwill-reproduction', 'partial-refund'])
        ->and($hints['goodwill-reproduction'])->toContain('Kulanz')->toContain('nie bei berechtigter Reklamation')
        ->and($hints['reproduction'])->toContain('berechtigten Reklamation');
});
