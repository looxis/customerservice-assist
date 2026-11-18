@props(['days'])

{{-- Recent changes on the start page, floating at the bottom right: at most a third of the window in height
     and (from lg on, below that the width of the window) in width; longer text scrolls inside.
     Closing hides the notice until the next day, or until a newer day is added. --}}
@if ($days->isNotEmpty())
    @php
        $latest = $days->first()['date']->format('Y-m-d');
    @endphp
    <section aria-labelledby="recent-changes-heading" class="fixed right-4 bottom-4 z-30 flex max-h-[33vh] w-[calc(100vw-2rem)] max-w-sm flex-col rounded-lg bg-notice-50 shadow-3 ring-1 ring-notice-200 wrap-anywhere lg:w-[33vw] lg:max-w-none"
             x-data="{
                 key: 'recentChanges.closed',
                 stamp() { const now = new Date(); return [now.getFullYear(), String(now.getMonth() + 1).padStart(2, '0'), String(now.getDate()).padStart(2, '0')].join('-') + '|{{ $latest }}' },
                 open: true,
                 init() { try { this.open = localStorage.getItem(this.key) !== this.stamp() } catch (error) {} },
                 close() { this.open = false; try { localStorage.setItem(this.key, this.stamp()) } catch (error) {} },
             }"
             x-cloak x-show="open">
        <div class="flex shrink-0 items-start justify-between gap-3 px-5 pt-4 pb-2">
            <h2 id="recent-changes-heading" class="text-base font-semibold text-slate-900">Aktuelle Änderungen</h2>
            <button type="button" x-on:click="close()" aria-label="Für heute schließen" title="Für heute schließen"
                    class="-mt-1 -mr-2 rounded-md p-1.5 text-slate-500 hover:bg-notice-200/60 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand">
                <x-icon name="x" />
            </button>
        </div>

        <div class="min-h-0 overflow-y-auto px-5 pb-4" tabindex="0" aria-label="Aktuelle Änderungen, scrollbar">
            <x-changelog.days :days="$days" />

            <p class="mt-4 text-sm">
                <a href="{{ route('changes') }}" class="font-semibold text-brand hover:text-brand-hover">Alle Änderungen ansehen</a>
            </p>
        </div>
    </section>
@endif
