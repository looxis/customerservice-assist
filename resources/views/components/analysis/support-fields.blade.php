@props(['inputs' => []])

@php
    $oldInput = session()->hasOldInput();
    $pick = fn (string $key, mixed $default = null) => $oldInput ? old($key, $default) : ($inputs[$key] ?? $default);
    $answers = ['ja' => 'Ja', 'unklar' => 'Unklar, bitte weitere Informationen erfragen'];
@endphp

{{-- The guided form for technical support (PROJ-90). The Alpine property loesung lives in the form. The branches are
     disabled fieldsets: what is not shown is not sent, but typed values stay when the choice changes. --}}
<fieldset x-show="mode === 'support'" x-bind:disabled="mode !== 'support'" x-cloak class="min-w-0 space-y-5 border-0 p-0">
    <legend class="sr-only">Technischer Support</legend>

    <x-analysis.choice name="support_loesung" legend="Ist eine mögliche Lösung bereits bekannt?" :options="$answers" model="loesung" :value="$pick('support_loesung')" :required="true" />

    {{-- Known: an optional note on the solution, the rest comes from the knowledge of the channel. --}}
    <fieldset x-show="loesung === 'ja'" x-bind:disabled="loesung !== 'ja'" x-cloak class="min-w-0 border-0 p-0">
        <legend class="sr-only">Lösung bekannt</legend>
        <x-textarea name="support_hinweis" id="support_hinweis" label="Zusätzliche Hinweise zur Lösung – optional" rows="3" maxlength="1000" placeholder="z. B. Passwort-vergessen-Funktion nutzen" :value="$pick('support_hinweis', '')" />
    </fieldset>

    {{-- Unclear: what the customer still has to tell us, on top of the standard request of the knowledge base (TONE-014). --}}
    <fieldset x-show="loesung === 'unklar'" x-bind:disabled="loesung !== 'unklar'" x-cloak class="min-w-0 border-0 p-0">
        <legend class="sr-only">Lösung unklar</legend>
        <x-textarea name="support_info" id="support_info" label="Welche Informationen werden benötigt? – optional" rows="3" maxlength="1000" placeholder="z. B. bitte die Fehlermeldung im Wortlaut nennen"
                    hint="Um einen Screenshot sowie Betriebssystem und Browser mit Version bittet die Antwort immer. Was du hier einträgst, steht an erster Stelle." :value="$pick('support_info', '')" />
    </fieldset>
</fieldset>
