<?php

use App\Analysis\Agents\CaseAgent;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
    ]);

    knowledgeBase([
        'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
        'procedures/procedure-001-a.md' => knowledgeDoc(['id' => 'PROCEDURE-001', 'type' => 'procedure', 'title' => 'Retoure erfassen', 'actions' => ['return'], 'categories' => ['complaint']], "Gilt für alle.\n\n# Voraussetzungen\n\n- x\n\n# Arbeitsschritte\n\n1. Retoure anlegen.\n\n# Abschlusskontrolle\n\n- [ ] Fertig\n"),
    ]);
    fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Paket kam zurück.</p>'])]);
    Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
    CaseAgent::fake([[
        'summary' => ['incident' => 'Retoure', 'customer_wish' => 'Info'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Retoure erfassen.', 'actions' => ['return'], 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Begründung der KI.', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
    ]]);
    $this->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf']);
});

afterEach(fn () => cleanUpKnowledgeBases());

test('the collapsible sections with the reasoning and the details are only shown in the expert mode', function () {
    $normal = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->getContent();
    $expert = $this->withCookie('staff_name', 'Nele')->withCookie('advanced_mode', '1')->get('/tickets/2137942')->assertOk()->getContent();

    foreach (['Begründung', 'Quellen', 'Kurzfassung', 'Details zum Vorschlag', 'Begründung der KI.'] as $text) {
        expect($normal)->not->toContain($text)->and($expert)->toContain($text);
    }
});

test('what to do, the internal procedures and the reply stay visible in this order for everybody', function () {
    $html = $this->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()->getContent();

    expect($html)->toContain('Retoure erfassen.')->toContain('Interne Abläufe')
        ->and(strpos($html, 'Was ist zu tun?'))->toBeLessThan(strpos($html, 'id="ablaeufe"'))
        ->and(strpos($html, 'id="ablaeufe"'))->toBeLessThan(strpos($html, 'aria-label="Antwortentwurf"'));
});
