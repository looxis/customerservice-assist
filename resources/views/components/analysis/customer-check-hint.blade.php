{{-- Guided fact-finding in the guided processing (PROJ-103): a chosen case pattern offers a check the customer can carry
     out; "übernehmen" records it as the next step on purpose. It sets the matching answer of the complaint or support
     form only when none is chosen yet, and never changes a choice the employee made. Reads mode, berechtigt and
     loesung of the form. --}}
<div x-show="$store.caseFindings.customerChecks.length > 0" x-cloak class="space-y-2 rounded-md bg-trust-500/5 p-3 ring-1 ring-trust-500/20 ring-inset">
    <p class="flex items-center gap-2 text-sm font-medium text-slate-900">
        <x-icon name="list-checks" size="16" class="text-trust-500" /> Aus der Sachverhaltsermittlung: Prüfung durch den Kunden möglich
    </p>
    <template x-for="check in $store.caseFindings.customerChecks" :key="check.id">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
            <span class="text-slate-900"><span x-text="check.name"></span> <span class="text-slate-600">(<span x-text="check.knowledge"></span>)</span></span>
            <template x-if="! $store.caseFindings.isAdopted(check.id)">
                <x-button type="button" variant="secondary" class="px-2! py-1! text-xs!"
                          x-on:click="$store.caseFindings.adopt(check.id); if (mode === 'reklamation' && ! berechtigt) { berechtigt = 'info' } if (mode === 'support' && ! loesung) { loesung = 'unklar' }">übernehmen</x-button>
            </template>
            <template x-if="$store.caseFindings.isAdopted(check.id)">
                <span class="flex items-center gap-2">
                    <x-badge compact tone="success">übernommen</x-badge>
                    <button type="button" class="text-xs font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand" x-on:click="$store.caseFindings.withdraw(check.id)">zurücknehmen</button>
                </span>
            </template>
        </div>
    </template>
    <p class="text-xs text-slate-600" x-show="mode === 'reklamation'">Passende Wahl: „Unsicher, weitere Informationen nötig“. Die Anleitung für den Kunden kommt aus dem Wissen.</p>
    <p class="text-xs text-slate-600" x-show="mode === 'support'">Passende Wahl: „Unklar, bitte weitere Informationen erfragen“. Die Anleitung für den Kunden kommt aus dem Wissen.</p>
    <p class="text-xs font-medium text-warning-700" role="status"
       x-show="$store.caseFindings.adopted.length > 0 && ((mode === 'reklamation' && berechtigt && berechtigt !== 'info') || (mode === 'support' && loesung && loesung !== 'unklar'))">
        Die übernommene Prüfung passt nicht zur gewählten Antwort im Formular. Deine Wahl bleibt; die KI weist auf den Widerspruch hin.
    </p>
</div>
