@props(['number', 'state'])

{{-- The category of the first customer message (PROJ-80): the staff confirms or corrects the proposal of the model, or
     marks the ticket as unsure. The choice is sent in the background, so that a started reply stays. A ticket of the
     test set shows no proposal, so that the choice is not swayed by it. --}}
<div x-data="{
            state: @js($state),
            busy: false,
            error: null,
            changing: false,
            async choose(decision) {
                if (this.busy) return;
                this.busy = true;
                this.error = null;
                try {
                    const response = await fetch(@js(route('tickets.label', ['number' => $number])), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ entscheidung: decision }),
                    });
                    const data = await response.json().catch(() => ({}));
                    if (! response.ok) {
                        this.error = data.message ?? 'Die Einordnung konnte nicht gespeichert werden. Bitte versuche es erneut.';
                        return;
                    }
                    this.state = data;
                    this.changing = false;
                } catch (e) {
                    this.error = 'Keine Verbindung. Bitte versuche es erneut.';
                } finally {
                    this.busy = false;
                }
            },
        }"
        role="region" aria-labelledby="klassifikation-titel">
<x-card class="space-y-3">
    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm">
        <h2 id="klassifikation-titel" class="font-semibold text-slate-900">Worum geht es?</h2>

        <template x-if="state.decision === null && state.suggestion !== null">
            <span class="flex flex-wrap items-center gap-2 text-slate-600">KI-Vorschlag: <x-badge tone="info"><span x-text="state.suggestion_label"></span></x-badge></span>
        </template>
        <template x-if="state.decision === null && state.suggestion === null">
            <span class="text-slate-600">Wähle die Kategorie der ersten Kundennachricht.</span>
        </template>
        <template x-if="state.decision !== null">
            <span class="flex flex-wrap items-center gap-2 text-slate-600">
                <x-badge tone="success"><span x-text="state.decision_label"></span></x-badge>
                <span x-show="state.decided_by" x-text="'von ' + state.decided_by"></span>
                <x-badge x-show="state.waiting" tone="warning">Weicht von der KI ab, ein Admin prüft</x-badge>
                <button type="button" x-show="state.editable && ! changing" x-on:click="changing = true" class="font-medium text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand">Ändern</button>
            </span>
        </template>
        <template x-if="state.decision === null && state.unsure_by">
            <x-badge tone="warning"><span x-text="'Unsicher (' + state.unsure_by + ', ' + state.unsure_at + '), ein Admin prüft'"></span></x-badge>
        </template>
    </div>

    <x-intro>
        <p class="text-sm text-slate-600">Deine Wahl wird zu Trainingsdaten für die automatische Einordnung. Stimmt sie mit dem KI-Vorschlag überein, gilt sie; weicht sie ab, entscheidet ein Admin. Fahre über eine Kategorie, um ihre Regeln zu sehen.</p>
        <p x-show="state.test" class="text-sm text-slate-600">Bei diesem Ticket bleibt der KI-Vorschlag verborgen, damit deine Wahl unabhängig ist.</p>
    </x-intro>

    <div x-show="state.editable && (state.decision === null || changing)" x-cloak class="flex flex-wrap gap-2" x-bind:class="busy && 'pointer-events-none opacity-60'" x-bind:aria-busy="busy">
        @foreach (\App\Labeling\LabelCategory::cases() as $category)
            <x-tooltip>
                <x-button :variant="$state['suggestion'] === $category->value ? 'primary' : 'secondary'" x-on:click="choose('{{ $category->value }}')" x-bind:disabled="busy">
                    @if ($state['suggestion'] === $category->value)<x-icon name="check" :size="16" />@endif
                    {{ $category->label() }}
                    <x-badge x-show="state.decision === '{{ $category->value }}'" compact tone="info">bisher</x-badge>
                </x-button>
                <x-slot:content><x-labeling.category-rules :rules="$category->rules()" /></x-slot:content>
            </x-tooltip>
        @endforeach
        <x-tooltip align="right" x-show="! state.unsure_by">
            <x-button variant="secondary" x-on:click="choose('{{ \App\Models\LabelTicket::UNSURE }}')" x-bind:disabled="busy">
                <x-icon name="help" :size="16" /> Unsicher
            </x-button>
            <x-slot:content><x-labeling.category-rules :rules="['Du kannst dich nicht entscheiden: ein Admin prüft das Ticket', 'Bei mehreren Anliegen zählt das Hauptanliegen, nicht Nebenfragen']" /></x-slot:content>
        </x-tooltip>
    </div>

    <p x-show="busy" x-cloak role="status" class="text-sm text-slate-600">Wird gespeichert …</p>
    <p x-show="error" x-cloak role="alert" class="text-sm text-danger-700" x-text="error"></p>
</x-card>
</div>
