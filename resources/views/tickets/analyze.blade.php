<x-layouts.app title="Tickets" width="6xl">
    <div class="mx-auto w-full max-w-3xl space-y-6">
    <x-staff.hint :names="$staffNames" :current="$currentStaff" />

    <x-changelog.notice :days="$recentChanges" />
    <x-ticket.lookup />
    </div>

    <div class="mb-8">
        <x-todo.panel :view="$todoView" :counts="$todoCounts" :list="$todoList" />
    </div>

    <x-ticket.list :todo-tickets="$todoTickets" :filter="$ticketFilter" :rows="$ticketRows" :options="$ticketOptions" :sync="$ticketSync" :lacks-staff-user="$lacksStaffUser" />
</x-layouts.app>
