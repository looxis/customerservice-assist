@props(['group', 'value', 'canEdit' => false])

@php
    $source = old('settings.'.$group->key);
    $source = is_array($source) ? $source : $value;
    $rows = array_values(array_filter(array_map(function ($format) {
        if (! is_array($format)) {
            return null;
        }

        $examples = $format['examples'] ?? [];
        $counter = $format['counter_examples'] ?? [];

        return [
            'key' => (string) ($format['key'] ?? ''),
            'name' => (string) ($format['name'] ?? ''),
            'layout' => (string) ($format['layout'] ?? ''),
            'prefix' => (string) ($format['prefix'] ?? ''),
            'letters' => isset($format['letters']) ? (string) $format['letters'] : (($format['upper'] ?? false) ? 'upper' : 'both'),
            'needs' => array_values((array) ($format['needs'] ?? [])),
            'internal' => (bool) ($format['internal'] ?? false),
            'amazon' => (bool) ($format['amazon'] ?? false),
            'examples' => is_array($examples) ? implode("\n", $examples) : (string) $examples,
            'counter_examples' => is_array($counter) ? implode("\n", $counter) : (string) $counter,
        ];
    }, (array) $source)));
    $name = 'settings['.$group->key.']';
    $field = 'block w-full rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand disabled:bg-slate-50';
@endphp

<div class="mt-3" x-data="formatList(@js(['rows' => $rows]))">
    <input type="hidden" name="{{ $name }}[_][name]" value="">
    <div class="space-y-4">
        <template x-for="(row, index) in rows" :key="index">
            <article class="rounded-lg p-4 ring-1 ring-slate-200 ring-inset">
                <input type="hidden" :name="'{{ $name }}[' + index + '][key]'" :value="row.key">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-900"><span x-text="index + 1"></span>. <span x-text="row.name || 'Neues Format'"></span></p>
                    @if ($canEdit)
                        <div class="flex gap-1">
                            <button type="button" x-on:click="move(index, -1)" :disabled="index === 0" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 disabled:opacity-40" :aria-label="'Format ' + (index + 1) + ' nach oben'">↑ nach oben</button>
                            <button type="button" x-on:click="move(index, 1)" :disabled="index === rows.length - 1" class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 disabled:opacity-40" :aria-label="'Format ' + (index + 1) + ' nach unten'">↓ nach unten</button>
                            <button type="button" x-on:click="remove(index)" class="rounded-md px-2 py-1 text-xs font-semibold text-danger-700 ring-1 ring-danger-500/40 hover:bg-danger-500/10" :aria-label="'Format ' + (index + 1) + ' entfernen'">Entfernen</button>
                        </div>
                    @endif
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-900">Name
                        <input type="text" :name="'{{ $name }}[' + index + '][name]'" x-model="row.name" maxlength="60" @disabled(! $canEdit) class="mt-1 {{ $field }}">
                    </label>
                    <label class="block text-sm font-medium text-slate-900">Aufbau <span class="font-normal text-slate-600">(# Ziffer, ? Buchstabe, * Buchstabe oder Ziffer, sonst fester Text)</span>
                        <input type="text" :name="'{{ $name }}[' + index + '][layout]'" x-model="row.layout" maxlength="40" autocomplete="off" @disabled(! $canEdit) class="mt-1 font-mono {{ $field }}">
                    </label>
                    <label class="block text-sm font-medium text-slate-900">Vorsilbe <span class="font-normal text-slate-600">(optional, darf vor der Nummer stehen)</span>
                        <input type="text" :name="'{{ $name }}[' + index + '][prefix]'" x-model="row.prefix" maxlength="20" autocomplete="off" @disabled(! $canEdit) class="mt-1 font-mono {{ $field }}">
                    </label>
                    <label class="block text-sm font-medium text-slate-900">Buchstaben
                        <select :name="'{{ $name }}[' + index + '][letters]'" x-model="row.letters" @disabled(! $canEdit) class="mt-1 {{ $field }}">
                            <option value="both">Groß- und Kleinbuchstaben</option>
                            <option value="upper">nur Großbuchstaben (kleine werden umgewandelt)</option>
                        </select>
                    </label>
                </div>

                <fieldset class="mt-3">
                    <legend class="text-sm font-medium text-slate-900">Muss enthalten</legend>
                    <div class="mt-1 flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-900">
                        @foreach (['digit' => 'eine Ziffer', 'letter' => 'einen Buchstaben', 'lower' => 'einen Kleinbuchstaben', 'upper' => 'einen Großbuchstaben'] as $need => $label)
                            <label class="inline-flex items-center gap-2"><input type="checkbox" value="{{ $need }}" :name="'{{ $name }}[' + index + '][needs][]'" x-model="row.needs" @disabled(! $canEdit) class="size-4 rounded border-slate-300 text-brand focus:ring-brand"> {{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-slate-900">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" value="1" :name="'{{ $name }}[' + index + '][internal]'" x-model="row.internal" @disabled(! $canEdit) class="size-4 rounded border-slate-300 text-brand focus:ring-brand"> Interne EOCS-Nummer <span class="text-slate-600">(wird erst auf Klick geladen, Abfrage über die EOCS-ID)</span></label>
                    <label class="inline-flex items-center gap-2"><input type="checkbox" value="1" :name="'{{ $name }}[' + index + '][amazon]'" x-model="row.amazon" @disabled(! $canEdit) class="size-4 rounded border-slate-300 text-brand focus:ring-brand"> Amazon <span class="text-slate-600">(die Amazon-Nachricht nutzt diesen Aufbau; genau ein Format)</span></label>
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-900">Beispiele, die passen müssen <span class="font-normal text-slate-600">(eines je Zeile, 1 bis 5)</span>
                        <textarea :name="'{{ $name }}[' + index + '][examples]'" x-model="row.examples" rows="3" @disabled(! $canEdit) class="mt-1 font-mono {{ $field }}"></textarea>
                    </label>
                    <label class="block text-sm font-medium text-slate-900">Beispiele, die nicht passen dürfen <span class="font-normal text-slate-600">(optional)</span>
                        <textarea :name="'{{ $name }}[' + index + '][counter_examples]'" x-model="row.counter_examples" rows="3" @disabled(! $canEdit) class="mt-1 font-mono {{ $field }}"></textarea>
                    </label>
                </div>
            </article>
        </template>
    </div>
    @if ($canEdit)
        <button type="button" x-on:click="add()" class="mt-3 inline-flex cursor-pointer items-center gap-1 text-sm font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand"><x-icon name="plus" size="16" /> Format hinzufügen</button>
    @endif
</div>
