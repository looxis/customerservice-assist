@props(['inputs' => []])

@php
    $oldInput = session()->hasOldInput();
    $pick = fn (string $key, mixed $default = null) => $oldInput ? old($key, $default) : ($inputs[$key] ?? $default);
@endphp

{{-- The guided form "Diskussion beenden" (PROJ-98): a short, firm closing statement when the customer repeats the
     same arguments. Offered only for some categories and after several customer replies. A disabled fieldset is not sent. --}}
<fieldset x-show="mode === '{{ \App\Analysis\ClosingDecision::FORM }}'" x-bind:disabled="mode !== '{{ \App\Analysis\ClosingDecision::FORM }}'" x-cloak class="min-w-0 space-y-5 border-0 p-0">
    <legend class="sr-only">Diskussion beenden</legend>

    <x-intro>
        <p class="text-sm text-slate-700">Für Kunden, die nach unserer Erklärung immer wieder dieselben Fragen und Argumente bringen. Der Entwurf sagt kurz und deutlich: ausführlich geprüft und erklärt, nichts hinzuzufügen, die Diskussion ist beendet. Ohne erneute Erklärung, ohne Verständnisfloskeln. Bringt der Kunde wirklich neue Tatsachen, bitte ein anderes Formular wählen.</p>
    </x-intro>

    <x-analysis.choice name="abschluss_angebot" legend="Bleibt unser bisheriges Angebot bestehen?" :options="[\App\Analysis\ClosingDecision::OFFER_STANDS => 'Ja, die angebotenen Optionen gelten weiterhin', \App\Analysis\ClosingDecision::NO_OFFER => 'Nein, es ist kein Angebot offen']" model="angebot" :value="$pick('abschluss_angebot')" :required="true" />

    <x-textarea name="abschluss_kernaussage" id="abschluss_kernaussage" label="Kernaussage (optional)" rows="2" maxlength="300" placeholder="z. B. Ein Druckfehler ist nicht erkennbar." :value="$pick('abschluss_kernaussage', '')"
                hint="Nur ein Satz, falls der maßgebliche Grund noch einmal genannt werden soll. Leer: Der Entwurf nennt keinen Grund." />

    <x-intro>
        <p class="text-xs text-slate-600">Beim Senden schlägt die App eine interne Notiz für Zammad vor: Weitere Nachrichten ohne neue Hinweise werden nur noch geschlossen.</p>
    </x-intro>
</fieldset>
