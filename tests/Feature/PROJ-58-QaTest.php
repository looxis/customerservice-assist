<?php

use App\Analysis\Agents\CaseAgent;
use App\Eocs\EocsOrder;
use App\Knowledge\CaseLinks;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'cache.default' => 'array',
        'staff.admins' => ['Etienne'],
        'knowledge.tool_links' => ['eocs-kunden' => 'https://eocs.example/customers', 'vanilo-kunden' => 'https://shop.example/manage/shopper', 'mailcoach-listen' => 'https://mail.example/email-lists'],
    ]);

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
        'procedures/procedure-001-a.md' => knowledgeDoc(['id' => 'PROCEDURE-001', 'type' => 'procedure', 'title' => 'Ablauf', 'actions' => ['return'], 'categories' => ['complaint']], "Gilt für alle.\n\n# Voraussetzungen\n\n- x\n\n# Arbeitsschritte\n\n1. [Ticket öffnen](link:zammad-ticket), [Auftrag öffnen](link:eocs-auftrag) und [Konto suchen](link:vanilo-kunden).\n\n# Abschlusskontrolle\n\n- [ ] Fertig\n"),
    ]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function toolCaseAnswer(): array
{
    return [
        'summary' => ['incident' => 'Retoure', 'customer_wish' => 'Info'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Retoure.', 'actions' => ['return'], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ];
}

test('the ticket page asks for a hand-picked procedure with the ticket number and the order numbers', function () {
    fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Paket kam zurück.</p>'])]);
    CaseAgent::fake([toolCaseAnswer()]);
    $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf']);

    $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942?bestellungen[]=305-5614181-8221166')->assertOk()->getContent();

    expect($html)->toContain('__ID__?ticket=2137942')->toContain('bestellungen%5B0%5D=305-5614181-8221166');
});

test('a question page offers a procedure without a ticket, so only the fixed tools are buttons', function () {
    CaseAgent::fake([toolCaseAnswer()]);
    $location = $this->withCookie('staff_name', 'Nele')->post(route('questions.store'), withProductAnswer(['text' => 'Paket kam zurück, was tun?', 'kundengruppe' => 'reseller', 'produkte' => [], 'kein_produktbezug' => '1']))->assertRedirect()->headers->get('Location');

    $html = $this->withCookie('staff_name', 'Nele')->get($location)->assertOk()->getContent();

    expect($html)->toContain('Konto suchen')->toContain('href="https://shop.example/manage/shopper"')->not->toContain('Ticket öffnen</a>')->not->toContain('Auftrag öffnen</a>')
        ->and($html)->not->toContain('__ID__?ticket=');
});

test('a hand-picked procedure still opens when Zammad cannot be read, with the fixed buttons only', function () {
    Http::fake(['zammad.test/*' => Http::response(['error' => 'down'], 500), 'eocs.test/*' => Http::response(['data' => []])]);

    $html = $this->withCookie('staff_name', 'Nele')->get(route('procedures.show', ['id' => 'PROCEDURE-001', 'ticket' => '2137942', 'bestellungen' => ['305-5614181-8221166']]))->assertOk()->getContent();

    expect($html)->toContain('Konto suchen')->toContain('href="https://shop.example/manage/shopper"')->not->toContain('Ticket öffnen</a>');
});

test('a hand-picked procedure for an unknown id is not found, and twelve order numbers do not break the page', function () {
    $this->withCookie('staff_name', 'Nele')->get(route('procedures.show', ['id' => 'PROCEDURE-099']))->assertNotFound();

    fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>x</p>'])]);
    $many = array_map(fn (int $i): string => "305-000000{$i}-0000000", range(1, 12));

    $this->withCookie('staff_name', 'Nele')->get(route('procedures.show', ['id' => 'PROCEDURE-001', 'ticket' => '2137942', 'bestellungen' => $many]))->assertOk();
});

test('a link without a key or in capital letters is an error in the check', function () {
    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
        'procedures/procedure-001-a.md' => knowledgeDoc(['id' => 'PROCEDURE-001', 'type' => 'procedure', 'title' => 'Ablauf', 'actions' => ['return']], "Gilt für alle.\n\n# Voraussetzungen\n\n- x\n\n# Arbeitsschritte\n\n1. [Leer](link:) und [Groß](link:EOCS-AUFTRAG).\n\n# Abschlusskontrolle\n\n- [ ] Fertig\n"),
    ]);

    $this->artisan('knowledge:check')->expectsOutputToContain('Unbekanntes Ziel `link:`')->expectsOutputToContain('Unbekanntes Ziel `link:EOCS-AUFTRAG`')->assertFailed();
});

test('every link key used in the procedures of the knowledge base is a known key', function () {
    $keys = [];

    foreach (glob(base_path('knowledge/procedures/*.md')) as $file) {
        preg_match_all('/\]\(link:([^)\s]*)\)/u', (string) file_get_contents($file), $matches);
        $keys[basename($file)] = array_unique($matches[1]);
    }

    foreach ($keys as $file => $used) {
        foreach ($used as $key) {
            expect(CaseLinks::isKnown($key))->toBeTrue("{$file}: link:{$key}");
        }
    }

    expect($keys['procedure-004-fachhaendler-kundenkonto-schliessen.md'])->toEqualCanonicalizing(['mailcoach-listen', 'vanilo-kunden', 'eocs-kunden'])
        ->and($keys['procedure-005-neuproduktion-und-ersatzversand-als-reklamationsauftrag.md'])->toBe(['eocs-auftrag']);
});

test('a changed address in the configuration reaches the buttons without touching a procedure', function () {
    config(['knowledge.tool_links.mailcoach-listen' => 'https://neu.example/listen']);

    expect((new CaseLinks)->targets('mailcoach-listen')[0]['url'])->toBe('https://neu.example/listen');
});

test('the Amazon order link carries the order number, only for Amazon orders in the right format', function () {
    config(['knowledge.order_links.amazon-bestellung' => 'https://seller.example/orders/{number}']);
    $amazon = fn (string $number, ?string $channel = 'amazon') => new EocsOrder(1, $number, null, $channel, null, null, null, null, [], [], [], 'https://eocs.example/orders/1');

    expect((new CaseLinks([$amazon('306-7190795-2981965')]))->targets('amazon-bestellung'))->toBe([['url' => 'https://seller.example/orders/306-7190795-2981965', 'label' => null]])
        ->and(array_column((new CaseLinks([$amazon('306-7190795-2981965'), $amazon('306-1111111-2222222')]))->targets('amazon-bestellung'), 'label'))->toBe(['306-7190795-2981965', '306-1111111-2222222'])
        ->and((new CaseLinks([$amazon('306-7190795-2981965', 'looxis-de')]))->targets('amazon-bestellung'))->toBe([])
        ->and((new CaseLinks([$amazon('R1-306-7190795-2981965')]))->targets('amazon-bestellung'))->toBe([])
        ->and((new CaseLinks([$amazon('306-7190795-2981965/../x')]))->targets('amazon-bestellung'))->toBe([])
        ->and((new CaseLinks)->targets('amazon-bestellung'))->toBe([])
        ->and(CaseLinks::isKnown('amazon-bestellung'))->toBeTrue();
});
