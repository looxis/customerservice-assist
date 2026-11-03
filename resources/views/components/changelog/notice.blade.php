@props(['days'])

{{-- Recent changes on the start page. Closing hides the notice until the next day, or until a newer day is added. --}}
@if ($days->isNotEmpty())
    @php
        $latest = $days->first()['date']->format('Y-m-d');
    @endphp
    <section aria-labelledby="recent-changes-heading" class="relative rounded-lg bg-notice-50 p-6 shadow-1 ring-1 ring-notice-200 wrap-anywhere"
             x-data="{
                 key: 'recentChanges.closed',
                 stamp() { const now = new Date(); return [now.getFullYear(), String(now.getMonth() + 1).padStart(2, '0'), String(now.getDate()).padStart(2, '0')].join('-') + '|{{ $latest }}' },
                 open: true,
                 init() { try { this.open = localStorage.getItem(this.key) !== this.stamp() } catch (error) {} },
                 close() { this.open = false; try { localStorage.setItem(this.key, this.stamp()) } catch (error) {} },
             }"
             x-cloak x-show="open">
        <button type="button" x-on:click="close()" aria-label="Für heute schließen" title="Für heute schließen"
                class="absolute top-3 right-3 rounded-md p-1.5 text-slate-500 hover:bg-notice-200/60 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand">
            <x-icon name="x" />
        </button>

        <h2 id="recent-changes-heading" class="pr-8 text-base font-semibold text-slate-900">Aktuelle Änderungen</h2>

        <x-changelog.days :days="$days" class="mt-3" />

        <p class="mt-4 text-sm">
            <a href="{{ route('changes') }}" class="font-semibold text-brand hover:text-brand-hover">Alle Änderungen ansehen</a>
        </p>
    </section>
@endif
