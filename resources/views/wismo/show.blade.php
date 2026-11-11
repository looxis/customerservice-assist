<x-layouts.app title="WISMO-Antwort Ticket#{{ $run->ticket_number }}" width="5xl">
    <nav aria-label="Brotkrumen" class="text-sm"><a href="{{ route('wismo-runs.index') }}" class="font-medium text-brand hover:text-brand-hover">WISMO-Antworten</a> <span class="text-slate-500">/ Ticket#{{ $run->ticket_number }}</span></nav>

    <x-card>
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-base font-semibold text-slate-900">Ticket#{{ $run->ticket_number }}</h2>
            <x-badge :tone="match ($run->result) { 'sent' => 'success', 'dry_run' => 'info', 'error', 'sending' => 'danger', 'note', 'retry', 'working' => 'warning', default => 'neutral' }">{{ $run->label() }}</x-badge>
            <a href="{{ route('tickets.show', ['number' => $run->ticket_number]) }}" class="text-sm font-semibold text-brand hover:text-brand-hover">Ticket öffnen</a>
        </div>
        @if ($run->reason)<p class="mt-2 text-sm text-slate-700">{{ $run->reason }}</p>@endif

        <dl class="mt-4 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-600">Bestellnummer</dt><dd class="font-mono text-slate-900">{{ $run->order_number ?? '–' }}</dd></div>
            <div><dt class="text-slate-600">Carrier</dt><dd class="text-slate-900">{{ $run->carrier ?? '–' }}</dd></div>
            <div><dt class="text-slate-600">Sendungsnummer</dt><dd class="font-mono text-slate-900">{{ $run->tracking_number ?? '–' }}</dd></div>
            <div><dt class="text-slate-600">Zustellproblem</dt><dd class="text-slate-900">{{ $run->delivery_problem ? 'ja: '.$run->delivery_problem_reason : 'nein' }}</dd></div>
            <div><dt class="text-slate-600">Abholadresse genannt</dt><dd class="text-slate-900">{{ $run->pickup_address_used ? 'ja' : 'nein' }}</dd></div>
            <div><dt class="text-slate-600">Ohne Versand seit</dt><dd class="text-slate-900">{{ $run->stuck_workdays !== null ? $run->stuck_workdays.' Werktagen' : '–' }}</dd></div>
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
            <p class="mt-3 text-xs text-slate-600">Die Texte werden nach {{ \App\Models\WismoRun::TEXT_DAYS }} Tagen geleert.</p>
        </x-card>
    @else
        <x-alert>Es gibt keinen Antworttext (nicht erzeugt oder nach {{ \App\Models\WismoRun::TEXT_DAYS }} Tagen geleert).</x-alert>
    @endif
</x-layouts.app>
