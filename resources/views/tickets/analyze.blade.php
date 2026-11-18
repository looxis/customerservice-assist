<x-layouts.app title="Tickets" width="wide">
    <div class="mx-auto mb-8 w-full max-w-3xl space-y-6">
    <x-staff.hint :names="$staffNames" :current="$currentStaff" />

    {{-- Tickets come in by themselves (PROJ-66); the manual lookup stays folded and opens again with an error. --}}
    <x-collapsible title="Ticket aus Zammad manuell abrufen" :open="$errors->has('ticket')">
        <x-ticket.lookup bare label="Ticketnummer oder Adresse" />
    </x-collapsible>
    </div>

    <x-ticket.list :todo-tickets="$todoTickets" :filter="$ticketFilter" :rows="$ticketRows" :options="$ticketOptions" :sync="$ticketSync" :lacks-staff-user="$lacksStaffUser" :category-status="$categoryStatus" :locked-tickets="$lockedTickets ?? []" />

    <x-changelog.notice :days="$recentChanges" />
</x-layouts.app>
