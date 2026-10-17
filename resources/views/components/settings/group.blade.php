@props(['group', 'value', 'version', 'isDefault', 'last' => null, 'history', 'canEdit' => false, 'outdated' => false])

@php
    $name = 'settings['.$group->key.']';
    $messages = isset($errors) ? $errors->get('settings.'.$group->key) : [];
    $single = in_array($group->kind, [\App\Settings\SettingGroup::NUMBER, \App\Settings\SettingGroup::PROMPT, \App\Settings\SettingGroup::MODEL, \App\Settings\SettingGroup::FORMATS], true);
    $current = old('settings.'.$group->key, $single ? '' : $value);
    if ($group->kind === \App\Settings\SettingGroup::HOSTS && ! is_array($current)) { $current = $value; }
    $rows = $single ? [] : array_values((array) $current);
    $rows = $group->kind === \App\Settings\SettingGroup::HOSTS
        ? array_values(array_filter(array_map(fn ($row) => is_array($row) ? ['host' => (string) ($row['host'] ?? ''), 'mode' => ($row['mode'] ?? 'text') === 'clickable' ? 'clickable' : 'text'] : null, $rows), fn ($row) => $row !== null && $row['host'] !== ''))
        : array_values(array_filter(array_map(fn ($row) => is_string($row) ? $row : null, $rows), fn ($row) => $row !== null && $row !== ''));
    $kinds = ['gespeichert' => 'gespeichert', 'wiederhergestellt' => 'wiederhergestellt', 'standard' => 'auf Standard zurückgesetzt'];
@endphp

<x-card id="{{ $group->key }}" class="scroll-mt-4">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <h2 class="text-base font-semibold text-slate-900">{{ $group->title }}</h2>
        @if ($isDefault)
            <x-badge compact>Standard der App</x-badge>
        @elseif ($last)
            <x-badge compact tone="info">geändert von {{ $last['by'] }}, {{ \Carbon\CarbonImmutable::parse($last['at'])->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr</x-badge>
        @endif
    </div>
    <p class="mt-1 text-sm text-slate-600">{{ $group->hint }}</p>

    <input type="hidden" name="versions[{{ $group->key }}]" value="{{ $version }}">

    @foreach ($messages as $message)
        <p class="mt-2 text-sm font-medium text-danger-700" role="alert">{{ $message }}</p>
    @endforeach

    @if ($group->kind === \App\Settings\SettingGroup::FORMATS)
        <x-settings.formats :group="$group" :value="$value" :can-edit="$canEdit" />
    @elseif ($group->kind === \App\Settings\SettingGroup::PROMPT)
        @if ($outdated)
            <p class="mt-2 text-sm font-medium text-warning-700">Der Standard der App wurde seit deiner Änderung erneuert. Deine Fassung gilt weiter; „Auf Standard der App zurücksetzen“ übernimmt den neuen Standard.</p>
        @endif
        @php($promptText = old('settings.'.$group->key, $value['text']))
        <div class="mt-3" x-data="{ length: @js(mb_strlen((string) $promptText)) }">
            <label class="sr-only" for="{{ $group->key }}-text">{{ $group->title }}</label>
            <textarea id="{{ $group->key }}-text" name="{{ $name }}" rows="14" @disabled(! $canEdit) x-on:input="length = $event.target.value.length"
                      class="block w-full rounded-md border-0 bg-white px-3 py-2 font-mono text-xs leading-relaxed text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand disabled:bg-slate-50">{{ $promptText }}</textarea>
            <p class="mt-1 text-right text-xs text-slate-600"><span x-text="length.toLocaleString('de-DE')"></span> / {{ number_format(\App\Settings\SettingGroup::PROMPT_MAX, 0, ',', '.') }} Zeichen</p>
        </div>
    @elseif ($group->kind === \App\Settings\SettingGroup::MODEL)
        <div class="mt-3">
            <label class="sr-only" for="{{ $group->key }}-modell">{{ $group->title }}</label>
            <input type="text" id="{{ $group->key }}-modell" name="{{ $name }}" list="modelle" value="{{ old('settings.'.$group->key, $value) }}" maxlength="100" autocomplete="off" @disabled(! $canEdit)
                   class="block w-full max-w-sm rounded-md border-0 bg-white px-3 py-1.5 font-mono text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand disabled:bg-slate-50">
        </div>
    @elseif ($group->kind === \App\Settings\SettingGroup::NUMBER)
        <div class="mt-3">
            <label class="sr-only" for="{{ $group->key }}-zahl">{{ $group->title }}</label>
            <input type="number" id="{{ $group->key }}-zahl" name="{{ $name }}" min="0" max="{{ $group->max }}" value="{{ old('settings.'.$group->key, $value) }}" @disabled(! $canEdit)
                   class="block w-24 rounded-md border-0 bg-white px-3 py-1.5 text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand disabled:bg-slate-50 sm:text-sm">
        </div>
    @else
        <div class="mt-3" x-data="settingsList(@js(['rows' => $rows, 'kind' => $group->kind, 'name' => $name]))">
            <input type="hidden" name="{{ $name }}{{ $group->kind === \App\Settings\SettingGroup::HOSTS ? '[_][host]' : '[]' }}" value="">
            <ul class="space-y-2">
                <template x-for="(row, index) in rows" :key="index">
                    <li class="flex items-start gap-2">
                        @if ($group->kind === \App\Settings\SettingGroup::HOSTS)
                            <input type="text" :name="name + '[' + index + '][host]'" x-model="row.host" maxlength="120" placeholder="z. B. looxis.de" autocomplete="off" @disabled(! $canEdit) :aria-label="'Adresse ' + (index + 1)"
                                   class="block min-w-0 flex-1 rounded-md border-0 bg-white px-3 py-1.5 font-mono text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand disabled:bg-slate-50">
                            <select :name="name + '[' + index + '][mode]'" x-model="row.mode" @disabled(! $canEdit) :aria-label="'Anzeige ' + (index + 1)"
                                    class="rounded-md border-0 bg-white py-1.5 pr-8 pl-3 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand disabled:bg-slate-50">
                                <option value="text">als Text anzeigen</option>
                                <option value="clickable">klickbar lassen</option>
                            </select>
                        @elseif ($group->kind === \App\Settings\SettingGroup::LINES)
                            <textarea :name="name + '[]'" x-model="rows[index]" rows="3" maxlength="{{ $group->maxLength }}" @disabled(! $canEdit) :aria-label="'Eintrag ' + (index + 1)"
                                      class="block min-w-0 flex-1 rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand disabled:bg-slate-50"></textarea>
                        @else
                            <input type="text" :name="name + '[]'" x-model="rows[index]" maxlength="{{ $group->maxLength }}" autocomplete="off" @disabled(! $canEdit) :aria-label="'Eintrag ' + (index + 1)"
                                   class="block min-w-0 flex-1 rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand disabled:bg-slate-50">
                        @endif
                        @if ($canEdit)
                            <button type="button" x-on:click="remove(index)" class="inline-flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-md text-slate-600 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-brand" :aria-label="'Eintrag ' + (index + 1) + ' entfernen'">
                                <x-icon name="x" size="16" />
                            </button>
                        @endif
                    </li>
                </template>
            </ul>
            <p class="text-sm text-slate-600" x-show="rows.length === 0" x-cloak>Keine Einträge.</p>
            @if ($canEdit)
                <button type="button" x-on:click="add()" class="mt-2 inline-flex cursor-pointer items-center gap-1 text-sm font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand">
                    <x-icon name="plus" size="16" /> Eintrag hinzufügen
                </button>
            @endif
        </div>
    @endif

    <details class="group/history mt-4">
        <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
            <x-icon name="chevron-right" size="16" class="transition group-open/history:rotate-90" /> Verlauf ({{ $history->count() }})
        </summary>
        <div class="mt-2 text-sm">
            @forelse ($history as $entry)
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-slate-100 py-2">
                    <span class="font-medium text-slate-900">Version {{ $entry->version }}</span>
                    <span class="text-slate-600">{{ $kinds[$entry->kind] ?? $entry->kind }} von {{ $entry->changed_by }}, {{ $entry->created_at->setTimezone('Europe/Berlin')->format('d.m.Y, H:i') }} Uhr</span>
                    <details class="basis-full">
                        <summary class="cursor-pointer text-xs font-medium text-slate-600 hover:text-slate-900">Wert dieser Version</summary>
                        <pre class="mt-1 max-h-48 overflow-y-auto whitespace-pre-wrap rounded-md bg-slate-50 p-2 font-mono text-xs text-slate-900">{{ is_array($entry->value) && isset($entry->value['text']) ? $entry->value['text'] : (is_array($entry->value) && isset($entry->value[0]['layout']) ? collect($entry->value)->map(fn ($format) => $format['name'].': '.$format['layout'].(! empty($format['prefix']) ? ' (Vorsilbe '.$format['prefix'].')' : ''))->implode("\n") : (is_array($entry->value) ? collect($entry->value)->map(fn ($item) => is_array($item) ? ($item['host'] ?? '').' ('.(($item['mode'] ?? '') === 'clickable' ? 'klickbar' : 'Text').')' : (string) $item)->implode("\n") : $entry->value)) }}</pre>
                    </details>
                    @if ($canEdit)
                        <button type="submit" form="wiederherstellen-{{ $group->key }}-{{ $entry->version }}" class="text-xs font-semibold text-brand hover:text-brand-hover">Diese Version wiederherstellen</button>
                    @endif
                </div>
            @empty
                <p class="text-slate-600">Noch keine Änderung; es gilt der Standard der App.</p>
            @endforelse
            @if ($canEdit && ! $isDefault)
                <p class="mt-2"><button type="submit" form="standard-{{ $group->key }}" class="text-sm font-semibold text-danger-700 hover:underline" onclick="return confirm('Auf den Standard der App zurücksetzen?')">Auf Standard der App zurücksetzen</button></p>
            @endif
        </div>
    </details>
</x-card>
