<?php

use App\Analysis\Agents\CaseAgent;
use App\Eocs\EocsClaim;
use App\Eocs\EocsOrder;
use App\Knowledge\CaseLinks;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeMarkdown;
use App\Knowledge\ProcedureRenderer;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
        'staff.admins' => ['Etienne'],
        'knowledge.tool_links' => [
            'eocs-kunden' => 'https://eocs.example/customers',
            'vanilo-kunden' => 'https://shop.example/manage/shopper',
            'mailcoach-listen' => 'https://mail.example/email-lists',
        ],
    ]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function toolOrder(int $id, string $number, array $claims = []): EocsOrder
{
    return new EocsOrder($id, $number, null, null, null, null, null, null, [], [], $claims, "https://eocs.example/orders/{$id}");
}

function toolClaim(int $id, string $number): EocsClaim
{
    return new EocsClaim($id, $number, null, null, null, "https://eocs.example/orders/{$id}");
}

function toolHtml(string $markdown, ?CaseLinks $case = null): string
{
    return app(KnowledgeMarkdown::class)->render($markdown, [], $case)->toHtml();
}

describe('the targets', function () {
    test('one order gives one button without a label, several orders one each with the number, claims included', function () {
        $one = new CaseLinks([toolOrder(11, '305-1')]);
        $two = new CaseLinks([toolOrder(11, '305-1', [toolClaim(12, 'R1-305-1')]), toolOrder(21, '305-2')]);

        expect($one->targets('eocs-auftrag'))->toBe([['url' => 'https://eocs.example/orders/11', 'label' => null]])
            ->and(array_column($two->targets('eocs-auftrag'), 'label'))->toBe(['305-1', 'R1-305-1', '305-2'])
            ->and((new CaseLinks)->targets('eocs-auftrag'))->toBe([]);
    });

    test('Vanilo gets the e-mail address encoded and the sort order, without an address the plain page', function () {
        $with = new CaseLinks(customerEmail: 'info+shop@foto-purkart.at');
        $without = new CaseLinks;

        expect($with->targets('vanilo-kunden')[0]['url'])->toBe('https://shop.example/manage/shopper?email=info%2Bshop%40foto-purkart.at&order_by=newest_first')
            ->and($without->targets('vanilo-kunden')[0]['url'])->toBe('https://shop.example/manage/shopper');
    });

    test('EOCS customers and Mailcoach never get a search term', function () {
        $case = new CaseLinks(customerEmail: 'kunde@example.org');

        expect($case->targets('eocs-kunden')[0]['url'])->toBe('https://eocs.example/customers')
            ->and($case->targets('mailcoach-listen')[0]['url'])->toBe('https://mail.example/email-lists');
    });

    test('only https addresses are used and unknown keys have no target', function () {
        config(['knowledge.tool_links.eocs-kunden' => 'http://eocs.example/customers']);

        expect((new CaseLinks)->targets('eocs-kunden'))->toBe([])->and((new CaseLinks)->targets('gibt-es-nicht'))->toBe([])
            ->and(CaseLinks::isKnown('eocs-auftrag'))->toBeTrue()->and(CaseLinks::isKnown('gibt-es-nicht'))->toBeFalse();
    });
});

describe('the display', function () {
    test('a link with a key becomes a button to a new tab, an ordinary https link stays as it was', function () {
        $html = toolHtml('Siehe [Auftrag öffnen](link:eocs-auftrag) und [Hilfe](https://example.org/hilfe).', new CaseLinks([toolOrder(11, '305-1')]));

        expect($html)->toContain('<a href="https://eocs.example/orders/11" target="_blank" rel="noopener noreferrer" class="procedure-link">Auftrag öffnen</a>')
            ->and($html)->toContain('href="https://example.org/hilfe"')->not->toContain('link:');
    });

    test('several orders give a button each, labelled with the number', function () {
        $html = toolHtml('[Auftrag öffnen](link:eocs-auftrag)', new CaseLinks([toolOrder(11, '305-1'), toolOrder(21, '305-2')]));

        expect($html)->toContain('Auftrag öffnen (305-1)')->toContain('Auftrag öffnen (305-2)')->and(substr_count($html, 'procedure-link'))->toBe(2);
    });

    test('without a target only the text stays, no empty button', function () {
        $html = toolHtml('Zuerst [Auftrag öffnen](link:eocs-auftrag), dann [Ticket](link:zammad-ticket).');

        expect($html)->toContain('Zuerst Auftrag öffnen, dann Ticket.')->not->toContain('<a ')->not->toContain('link:');
    });

    test('the document view shows fixed tools as buttons and everything that depends on a case as text', function () {
        $html = toolHtml('[Vanilo](link:vanilo-kunden) und [Auftrag](link:eocs-auftrag)');

        expect($html)->toContain('href="https://shop.example/manage/shopper"')->toContain('und Auftrag')->not->toContain('orders/');
    });

    test('text from the e-mail is escaped in the address and raw HTML in a label stays escaped', function () {
        $html = toolHtml('[<b>x</b>](link:vanilo-kunden)', new CaseLinks(customerEmail: '"><script>alert(1)</script>@x.de'));

        expect($html)->not->toContain('<script>')->not->toContain('<b>x</b>')->toContain('%22%3E%3Cscript%3E');
    });

    test('the procedure card keeps its checkboxes next to the button', function () {
        knowledgeBase(['procedures/procedure-001-a.md' => knowledgeDoc(['id' => 'PROCEDURE-001', 'type' => 'procedure', 'title' => 'Ablauf', 'actions' => ['return']], "Gilt für alle.\n\n# Voraussetzungen\n\n- Paket liegt vor.\n\n# Arbeitsschritte\n\n1. [Auftrag öffnen](link:eocs-auftrag) und prüfen.\n\n# Abschlusskontrolle\n\n- [ ] Fertig\n")]);
        $document = app(KnowledgeLibrary::class)->all()->first(fn ($candidate) => $candidate->id === 'PROCEDURE-001');

        $html = app(ProcedureRenderer::class)->render($document, new CaseLinks([toolOrder(11, '305-1')]))->toHtml();

        expect($html)->toContain('class="procedure-link"')->toContain('type="checkbox"');
    });
});

describe('the check', function () {
    test('an unknown key is an error, a known one is not', function () {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
            'procedures/procedure-001-a.md' => knowledgeDoc(['id' => 'PROCEDURE-001', 'type' => 'procedure', 'title' => 'Ablauf', 'actions' => ['return']], "Gilt für alle.\n\n# Voraussetzungen\n\n- x\n\n# Arbeitsschritte\n\n1. [Auftrag](link:eocs-auftrag) und [Falsch](link:eocs-auftag).\n\n# Abschlusskontrolle\n\n- [ ] Fertig\n"),
        ]);

        $this->artisan('knowledge:check')->expectsOutputToContain('Unbekanntes Ziel `link:eocs-auftag`')->assertFailed();
    });
});

describe('on the pages', function () {
    function toolAnalysis(array $actions = ['return']): string
    {
        knowledgeBase([
            'policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel']),
            'procedures/procedure-001-a.md' => knowledgeDoc(['id' => 'PROCEDURE-001', 'type' => 'procedure', 'title' => 'Ablauf', 'actions' => ['return'], 'categories' => ['complaint']], "Gilt für alle.\n\n# Voraussetzungen\n\n- x\n\n# Arbeitsschritte\n\n1. [Ticket öffnen](link:zammad-ticket) und [Konto suchen](link:vanilo-kunden).\n\n# Abschlusskontrolle\n\n- [ ] Fertig\n"),
        ]);
        fakeZammad([zammadArticle(['id' => 1, 'body' => '<p>Paket kam zurück.</p>'])]);
        Http::fake(['eocs.test/*' => Http::response(['data' => []])]);
        CaseAgent::fake([[
            'summary' => ['incident' => 'Retoure', 'customer_wish' => 'Info'], 'category' => 'complaint', 'case_pattern' => null, 'assessment' => null,
            'recommendation' => 'Retoure.', 'actions' => $actions, 'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
            'reasoning' => 'x', 'knowledge_gaps' => [], 'knowledge_ids' => [], 'confidence' => ['level' => 'MITTEL', 'reasons' => []],
            'next_step' => 'reply', 'decision' => null, 'internal_steps' => [], 'reply' => ['language' => 'Deutsch', 'text' => 'Guten Tag'],
        ]]);
        test()->withCookie('staff_name', 'Nele')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kein_produktbezug' => '1', 'kundengruppe' => 'reseller', 'variante' => 'verlauf']);

        return test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->getContent();
    }

    test('a suggested procedure on the ticket page links to the ticket and to Vanilo with the customer e-mail', function () {
        $html = toolAnalysis();

        expect($html)->toContain('class="procedure-link"')->toContain('Ticket öffnen')->toContain('https://shop.example/manage/shopper?email=')->toContain('order_by=newest_first');
    });

    test('a procedure picked by hand is loaded with the ticket and gets the same links', function () {
        toolAnalysis();

        $url = route('procedures.show', ['id' => 'PROCEDURE-001', 'ticket' => '2137942']);
        $html = $this->withCookie('staff_name', 'Nele')->get($url)->assertOk()->getContent();

        expect($html)->toContain('class="procedure-link"')->toContain('Konto suchen')->toContain('https://shop.example/manage/shopper?email=');
    });

    test('a procedure picked without a ticket or for a question has only the fixed buttons', function () {
        toolAnalysis();

        foreach ([route('procedures.show', ['id' => 'PROCEDURE-001']), route('procedures.show', ['id' => 'PROCEDURE-001', 'ticket' => 'F-1']), route('procedures.show', ['id' => 'PROCEDURE-001', 'ticket' => 'x"><script>'])] as $url) {
            $html = $this->withCookie('staff_name', 'Nele')->get($url)->assertOk()->getContent();
            expect($html)->toContain('Konto suchen')->toContain('href="https://shop.example/manage/shopper"')->not->toContain('Ticket öffnen</a>')->not->toContain('<script>');
        }
    });
});
