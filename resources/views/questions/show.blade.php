<x-layouts.app title="Frage" width="3xl">
    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Deine Frage</h2>
        <p class="mt-2 whitespace-pre-line text-sm text-slate-900">{{ $question['text'] }}</p>
        <p class="mt-3 text-xs text-slate-600">
            {{ $question['group_label'] ?? 'Noch unklar' }} · {{ $question['products_label'] ?? 'kein Produktbezug' }}
            @if ($question['placeholders'] ?? false) · Kontaktdaten wurden vor dem Senden an die KI durch Platzhalter ersetzt @endif
        </p>
    </x-card>

    <x-analysis.result :result="$result" :number="$number" :history="$history" :admin="$admin" :stand="null" :question="true" :advanced="$advancedModeActive ?? false" />

    <x-question.follow-up :result="$result" :number="$number" />

    <p class="text-sm"><a href="{{ route('questions.create') }}" class="font-semibold text-brand hover:text-brand-hover">Neue Frage stellen</a></p>
</x-layouts.app>
