<x-layouts.app title="Tickets" width="6xl">
    <div class="mx-auto w-full max-w-3xl space-y-6">
    <x-staff.hint :names="$staffNames" :current="$currentStaff" />

    <section aria-labelledby="recent-changes-heading" class="rounded-lg bg-notice-50 p-6 shadow-1 ring-1 ring-notice-200 wrap-anywhere">
        <h2 id="recent-changes-heading" class="text-base font-semibold text-slate-900">Aktuelle Änderungen</h2>

        <div class="mt-3 space-y-4 text-sm text-slate-700">

            <div>
                <p class="font-medium text-slate-900">2. Nov '26</p>
                <ul class="mt-1 list-disc space-y-1 pl-5">
                    <li>Tickets können jetzt ohne Antwort direkt geschlossen werden. Optional kann ein Grund angegeben werden.</li>
                </ul>
            </div>

            <div>
                <p class="font-medium text-slate-900">31. Okt '26</p>
                <ul class="mt-1 list-disc space-y-1 pl-5">
                    <li>Neue Ticketliste auf der Startseite: Neue und wieder geöffnete Tickets erscheinen automatisch, mit Ansichten und Filtern.</li>
                </ul>
            </div>

            <div>
                <p class="font-medium text-slate-900">27. Okt '26</p>
                  <ul class="mt-1 list-disc space-y-1 pl-5">
                    <li>DHL Retourenlabel können direkt aus einem Ticket, oder über die Navigation links erstellt werden.</li>
                    <li>Der von Thomas entwickelte Rabatt- & Angebots-Rechner ist aus passenden Tickets heraus und links über die Navigationsleiste erreichbar.</li>
                    <li>Dieses Info-Fenster :-)</li>
                </ul>
            </div>

            <div>
                <p class="font-medium text-slate-900">26. Okt '26</p>
                <ul class="mt-1 list-disc space-y-1 pl-5">
                    <li>Es können jetzt angehängte Kundenbilder direkt angeschaut werden.</li>
                    <li>Screenshots und Anhänge (z.B. PDF Dateien) können jetzt an eine Antwort angehangen werden.</li>
                </ul>
            </div>
        </div>
    </section>
    <x-ticket.lookup />
    </div>

    <x-ticket.list :filter="$ticketFilter" :rows="$ticketRows" :options="$ticketOptions" :sync="$ticketSync" :lacks-staff-user="$lacksStaffUser" />
</x-layouts.app>
