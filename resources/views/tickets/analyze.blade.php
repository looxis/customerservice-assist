<x-layouts.app title="Ticket analysieren">
    <x-staff.hint :names="$staffNames" :current="$currentStaff" />

    <section aria-labelledby="recent-changes-heading" class="rounded-lg bg-notice-50 p-6 shadow-1 ring-1 ring-notice-200 wrap-anywhere">
        <h2 id="recent-changes-heading" class="text-base font-semibold text-slate-900">Aktuelle Änderungen</h2>

        <h3 class="mt-4 text-sm font-semibold text-slate-900">Neue Funktionen</h3>

        <div class="mt-3 space-y-4 text-sm text-slate-700">
            <div>
                <p class="font-medium text-slate-900">27. Oktober '26</p>
                <p class="mt-1">
                    Dieses Info-Fenster &amp; DHL Retouren-Label erstellen in Vorbereitung.
                    <strong class="font-semibold">ACHTUNG: Noch ohne Funktion.</strong>
                </p>
            </div>

            <div>
                <p class="font-medium text-slate-900">26. Oktober '26</p>
                <ul class="mt-1 list-disc space-y-1 pl-5">
                    <li>Es können jetzt angehängte Kundenbilder direkt angeschaut werden.</li>
                    <li>Viel neues Wissen, u. a. rund um Stornierungen, Leuchtsockel, Fotokissen, Erinnerungsbox.</li>
                    <li>Gemeldete Wissenslücken sichtbar.</li>
                </ul>
            </div>
        </div>
    </section>
    <x-ticket.lookup />

    <x-card>
        <div class="flex items-start gap-3">
            <x-icon name="sparkle" class="mt-0.5 text-brand" />
            <div>
                <h2 class="text-base font-semibold text-slate-900">So geht es weiter</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Nach dem Laden siehst du den ganzen Verlauf des Tickets. Der Vorschlag mit Bewertung, Empfehlung und Antwortentwurf folgt in einem nächsten Schritt.
                </p>
            </div>
        </div>
    </x-card>
</x-layouts.app>
