<x-layouts.app title="Ticket analysieren">
    <x-staff.hint :names="$staffNames" :current="$currentStaff" />

    <x-ticket.lookup />

    <x-card>
        <div class="flex items-start gap-3">
            <x-icon name="sparkle" class="mt-0.5 text-brand" />
            <div>
                <h2 class="text-base font-semibold text-slate-900">So geht es weiter</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Nach dem Laden siehst du den ganzen Verlauf des Tickets. Die Analyse mit Bewertung, Empfehlung und Antwortentwurf folgt in einem nächsten Schritt.
                </p>
            </div>
        </div>
    </x-card>
</x-layouts.app>
