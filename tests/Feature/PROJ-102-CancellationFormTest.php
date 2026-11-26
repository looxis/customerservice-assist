<?php

use App\Analysis\Agents\CaseAgent;
use App\Analysis\CancellationDecision;
use App\Analysis\GuidedForms;
use App\Models\Analysis;
use App\Models\LabelTicket;
use App\Models\ReturnLabel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.zammad.url' => 'https://zammad.test', 'services.zammad.token' => 'z-token',
        'services.eocs.url' => 'https://eocs.test', 'services.eocs.token' => 'e-token',
        'ai.providers.openai.key' => 'sk-test',
    ]);

    knowledgeBase(['policies/policy-001-a.md' => knowledgeDoc(['title' => 'Allgemeine Regel'])]);
});

afterEach(fn () => cleanUpKnowledgeBases());

function cancellationTicket(bool $shipped = true): void
{
    fakeZammad([zammadArticle(['id' => 1, 'subject' => 'Storno', 'message_id' => '<m1@example.org>', 'body' => '<p>Bitte Bestellung 7JQ-18SU-DVA4 stornieren, ich will nicht für beide bezahlen.</p>'])]);
    $shipments = $shipped ? ['data' => [['carrier' => ['name' => 'DHL'], 'tracking_no' => '00340434161094042557', 'created_at' => '12.11.2026 09:00', 'delivered' => null]]] : ['data' => []];
    Http::fake([
        'eocs.test/api/v1/orders*' => function (Request $request) use ($shipments) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            [$number] = explode(',', (string) ($query['filter']['external_order_id'] ?? ''));

            return Http::response(['data' => $number === '7JQ-18SU-DVA4' ? [returnEocsOrder(['shipments' => $shipments])] : []]);
        },
    ]);
}

function cancellationCategory(string $category = 'storno'): void
{
    LabelTicket::query()->create(['zammad_id' => 51234, 'number' => '2137942', 'text' => 'Hallo', 'state' => 'classified', 'split' => 'training', 'decision' => $category, 'decision_origin' => 'zweifel']);
}

function cancellationAnswer(string $text = 'Hallo, eine Stornierung ist leider nicht mehr möglich.'): array
{
    return [
        'summary' => ['incident' => 'Stornowunsch', 'customer_wish' => 'Storno'],
        'category' => 'order-process-question', 'case_pattern' => null, 'assessment' => null,
        'recommendation' => 'Antwort senden.', 'actions' => [],
        'authority' => ['agent_may_decide' => true, 'approval_by' => null, 'permission_id' => null],
        'reasoning' => 'Formular.', 'knowledge_gaps' => [],
        'knowledge_ids' => [], 'confidence' => ['level' => 'HOCH', 'reasons' => []],
        'next_step' => 'reply', 'decision' => null, 'internal_steps' => [],
        'reply' => ['language' => 'Deutsch', 'text' => $text],
    ];
}

function cancellationRun(array $fields = [])
{
    return test()->withCookie('staff_name', 'Nele')->from('/tickets/2137942')->post(route('tickets.analysis.run', ['number' => '2137942']), ['kundengruppe' => 'private-looxis-de', 'verkaufskanal' => 'looxis-de', 'variante' => 'verlauf', 'formular' => 'storno', ...$fields]);
}

describe('when the form is offered', function () {
    test('preselected for cancellation and address change, a form for goods', function () {
        expect(GuidedForms::defaultFor('storno'))->toBe('storno')
            ->and(GuidedForms::defaultFor('adressaenderung'))->toBe('storno')
            ->and(GuidedForms::defaultFor('storno', true))->toBe('frei')
            ->and(GuidedForms::guidedLabel('storno'))->toBe('Storno / Änderung')
            ->and(GuidedForms::forGoods())->toContain('storno')
            ->and(GuidedForms::defaultFor('zahlung'))->toBe('zahlung');
    });

    test('the ticket page preselects it and lists the shipment', function () {
        cancellationTicket();
        cancellationCategory('adressaenderung');

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSee("mode: 'storno',", false)
            ->assertSee('name="formularwahl" value="storno"', false)
            ->assertSee('Adressänderung nicht mehr möglich, bereits verschickt')
            ->assertSee('Bestellung 7JQ-18SU-DVA4: DHL 00340434161094042557');
    });

    test('selectable for another category', function () {
        cancellationTicket();
        cancellationCategory('wismo');

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942')->assertOk()
            ->assertSee('name="formularwahl" value="storno"', false)
            ->assertDontSee("mode: 'storno',", false);
    });
});

describe('validation', function () {
    test('the outcome must be chosen', function () {
        cancellationTicket();

        cancellationRun()->assertSessionHasErrors(['storno_ergebnis' => 'Bitte das Ergebnis angeben.']);
    });

    test('done and not possible need the kind, a change done needs what was changed', function () {
        cancellationTicket();

        cancellationRun(['storno_ergebnis' => 'erledigt'])->assertSessionHasErrors(['storno_art']);
        cancellationRun(['storno_ergebnis' => 'nicht_moeglich'])->assertSessionHasErrors(['storno_art']);
        cancellationRun(['storno_ergebnis' => 'erledigt', 'storno_art' => 'aenderung'])->assertSessionHasErrors(['storno_aenderung' => 'Bitte angeben, was geändert wurde.']);
    });

    test('a changed address needs the address, a shipped one nothing more', function () {
        cancellationTicket();
        CaseAgent::fake([cancellationAnswer()]);

        cancellationRun(['storno_ergebnis' => 'adresse_geaendert'])->assertSessionHasErrors(['storno_adresse' => 'Bitte die neue Adresse angeben.']);
        cancellationRun(['storno_ergebnis' => 'adresse_versendet'])->assertSessionHasNoErrors();
    });

    test('no product, no free text and no return label go along', function () {
        cancellationTicket();
        CaseAgent::fake([cancellationAnswer()]);
        $label = ReturnLabel::factory()->create(['ticket_number' => '2137942']);

        cancellationRun(['storno_ergebnis' => 'nicht_moeglich', 'storno_art' => 'storno', 'produkt_ohne_wissen' => 'Fototasse', 'kein_produktbezug' => '0', 'kontext' => 'GEHEIMER FREITEXT', 'return_label' => $label->id])->assertSessionHasNoErrors();

        $inputs = Analysis::query()->latest()->first()->content['inputs'];
        expect((bool) $inputs['kein_produktbezug'])->toBeTrue()
            ->and($inputs['produkt_ohne_wissen'])->toBe('')
            ->and($inputs['return_label'])->toBeNull()
            ->and($inputs['formular'])->toBe('storno')
            ->and($inputs['storno_ergebnis'])->toBe('nicht_moeglich');
        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains('GEHEIMER FREITEXT'));
    });
});

describe('what the model is told', function () {
    test('not possible: in processing, no payment handling', function () {
        cancellationTicket();
        CaseAgent::fake([cancellationAnswer()]);

        cancellationRun(['storno_ergebnis' => 'nicht_moeglich', 'storno_art' => 'storno', 'storno_zusatz' => 'Zweite Bestellung läuft separat.'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Angaben aus dem Formular „Storno / Änderung“')
            && $prompt->contains('eine Stornierung ist deshalb nicht mehr möglich')
            && $prompt->contains('Gehe nicht auf Zahlungsabwicklung')
            && $prompt->contains('Zweite Bestellung läuft separat.'));
    });

    test('done: cancellation as an exception, a change with what was changed', function () {
        expect((new CancellationDecision('erledigt', 'storno'))->facts())->toContain('Wir haben die Bestellung storniert')->toContain('ausnahmsweise')->toContain('keine Erstattung')->toContain('das Ticket schließen')
            ->and((new CancellationDecision('erledigt', 'aenderung', 'Motiv getauscht'))->facts())->toContain('Motiv getauscht')->not->toContain('storniert')
            ->and((new CancellationDecision('nicht_moeglich', 'aenderung'))->facts())->toContain('eine Änderung ist deshalb nicht mehr möglich');
    });

    test('address changed: the address only as a placeholder', function () {
        cancellationTicket(false);
        CaseAgent::fake([cancellationAnswer("Hallo,\ndie neue Adresse lautet:\n[NEUE-LIEFERADRESSE]")]);

        cancellationRun(['storno_ergebnis' => 'adresse_geaendert', 'storno_adresse' => "Erika Beispiel\nMusterweg 12\n12345 Neustadt"])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('[NEUE-LIEFERADRESSE]')
            && ! $prompt->contains('Musterweg')
            && ! $prompt->contains('Neustadt'));

        expect(Analysis::query()->latest()->first()->content['placeholders']['[NEUE-LIEFERADRESSE]'])->toBe("Erika Beispiel\nMusterweg 12\n12345 Neustadt");
    });

    test('address shipped: tracking number, link and delivery options from EOCS', function () {
        cancellationTicket();
        CaseAgent::fake([cancellationAnswer()]);

        cancellationRun(['storno_ergebnis' => 'adresse_versendet'])->assertSessionHasNoErrors();

        CaseAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Sendungsnummer 00340434161094042557')
            && $prompt->contains('https://nolp.dhl.de/nextt-online-public/?piececode=00340434161094042557')
            && $prompt->contains('Zustelloptionen anpassen')
            && $prompt->contains('nicht in unserer Hand'));
    });

    test('address shipped without a shipment keeps a placeholder', function () {
        expect((new CancellationDecision('adresse_versendet'))->withShipments([])->facts())->toContain('[SENDUNGSNUMMER]')->toContain('intern ergänzen');
    });

    test('answers of another outcome are dropped', function () {
        $decision = CancellationDecision::fromInput(['formular' => 'storno', 'storno_ergebnis' => 'adresse_versendet', 'storno_art' => 'storno', 'storno_adresse' => 'Musterweg 12']);

        expect($decision->kind)->toBeNull()->and($decision->address)->toBe('')
            ->and(CancellationDecision::fromInput(['formular' => 'zahlung', 'storno_ergebnis' => 'erledigt']))->toBeNull();
    });
});

describe('the stored proposal', function () {
    test('the form is prefilled from the stored answers', function () {
        cancellationTicket();
        cancellationCategory();
        CaseAgent::fake([cancellationAnswer()]);

        cancellationRun(['storno_ergebnis' => 'nicht_moeglich', 'storno_art' => 'storno', 'storno_zusatz' => 'Schon verschickt.'])->assertSessionHasNoErrors();
        $id = Analysis::query()->latest()->first()->uuid;

        test()->withCookie('staff_name', 'Nele')->get('/tickets/2137942?analyse='.$id)->assertOk()
            ->assertSee("mode: 'storno',", false)
            ->assertSee("stornoErgebnis: 'nicht_moeglich',", false)
            ->assertSee("stornoArt: 'storno',", false)
            ->assertSee('Schon verschickt.')
            ->assertSee('Erstellt mit: Storno / Änderung', false);
    });
});
