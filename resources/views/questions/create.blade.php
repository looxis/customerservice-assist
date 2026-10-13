<x-layouts.app title="Frage stellen">
    <x-staff.hint :names="$staffNames" :current="$currentStaff" />

    <x-question.form :analysis="$form" />

    <x-question.recent :recent="$recent" />
</x-layouts.app>
