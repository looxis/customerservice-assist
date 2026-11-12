<x-layouts.app title="Vorab-Antwort Ticket#{{ $run->ticket_number }}" width="5xl">
    <nav aria-label="Brotkrumen" class="text-sm"><a href="{{ route('claim-reply-runs.index') }}" class="font-medium text-brand hover:text-brand-hover">Vorab-Antworten Reklamation</a> <span class="text-slate-500">/ Ticket#{{ $run->ticket_number }}</span></nav>

    <x-card>
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-base font-semibold text-slate-900">Ticket#{{ $run->ticket_number }}</h2>
            <x-badge :tone="match ($run->result) { 'sent' => 'success', 'dry_run' => 'info', 'error', 'sending' => 'danger', 'note', 'retry', 'working' => 'warning', default => 'neutral' }">{{ $run->label() }}</x-badge>
            <a href="{{ route('tickets.show', ['number' => $run->ticket_number]) }}" class="text-sm font-semibold text-brand hover:text-brand-hover">Ticket öffnen</a>
        </div>
        @if ($run->reason)<p class="mt-2 text-sm text-slate-700">{{ $run->reason }}</p>@endif

        <dl class="mt-4 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-600">Bestellnummer</dt><dd class="font-mono text-slate-900">{{ $run->order_number ?? '–' }}</dd></div>
            <div><dt class="text-slate-600">Fall</dt><dd class="text-slate-900">{{ $run->scenarioLabel() }}</dd></div>
            <div><dt class="text-slate-600">Fotos</dt><dd class="text-slate-900">{{ $run->photo_count ?? '–' }}</dd></div>
            <div><dt class="text-slate-600">Tage seit Versand</dt><dd class="text-slate-900">{{ $run->days_since_shipped ?? '–' }}</dd></div>
            <div><dt class="text-slate-600">Modus</dt><dd class="text-slate-900">{{ ['on' => 'an', 'dry' => 'Trockenlauf', 'off' => 'aus'][$run->mode] ?? $run->mode }}</dd></div>
            <div><dt class="text-slate-600">Prompt und Modell</dt><dd class="text-slate-900">{{ $run->prompt_version ?? '–' }} · {{ $run->model ?? '–' }}</dd></div>
            <div><dt class="text-slate-600">Tokens und Dauer</dt><dd class="text-slate-900">{{ $run->input_tokens !== null ? number_format($run->input_tokens, 0, ',', '.').' ein, '.number_format((int) $run->output_tokens, 0, ',', '.').' aus, '.number_format((int) $run->duration_ms / 1000, 1, ',', '.').' s' : '–' }}</dd></div>
        </dl>
    </x-card>

    @if ($run->reply_text)
        <x-card>
            <h2 class="text-base font-semibold text-slate-900">{{ $run->result === 'sent' ? 'Gesendete Antwort' : 'Antwort (nicht gesendet)' }}</h2>
            <pre class="mt-2 whitespace-pre-wrap rounded-md bg-slate-50 p-3 text-sm text-slate-900">{{ $run->reply_text }}</pre>
            @if ($run->reply_text_de && $run->reply_text_de !== $run->reply_text)
                <h3 class="mt-4 text-sm font-semibold text-slate-900">Deutsche Fassung</h3>
                <pre class="mt-2 whitespace-pre-wrap rounded-md bg-slate-50 p-3 text-sm text-slate-900">{{ $run->reply_text_de }}</pre>
            @endif
            <p class="mt-3 text-xs text-slate-600">Die Texte werden nach {{ \App\Models\ClaimReplyRun::TEXT_DAYS }} Tagen geleert.</p>
        </x-card>
    @else
        <x-alert>Es gibt keinen Antworttext (nicht erzeugt oder nach {{ \App\Models\ClaimReplyRun::TEXT_DAYS }} Tagen geleert).</x-alert>
    @endif
</x-layouts.app>
