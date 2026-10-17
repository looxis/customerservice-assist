@props(['group', 'value', 'canEdit' => false, 'options' => []])

@php
    $source = old('settings.'.$group->key);
    $source = is_array($source) ? $source : $value;
    $saved = collect((array) $value)->pluck('key')->filter()->all();
    $fields = array_column($group->columns, 'field');
    $rows = array_values(array_filter(array_map(function ($row) use ($fields, $saved, $group) {
        if (! is_array($row)) {
            return null;
        }

        $clean = [];

        foreach ($fields as $field) {
            $clean[$field] = isset($row[$field]) ? (string) $row[$field] : '';
        }

        $clean['_saved'] = in_array($row['key'] ?? null, $saved, true);

        return $clean;
    }, (array) $source), fn ($row) => $row !== null));
    $name = 'settings['.$group->key.']';
    $field = 'block w-full rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand disabled:bg-slate-50 read-only:bg-slate-50 read-only:text-slate-600';
    $moves = $group->key === 'kn_groups';
    $statusLabels = ['active' => 'aktiv', 'preparing' => 'in Vorbereitung', 'inactive' => 'deaktiviert'];
@endphp

<div class="mt-3" x-data="recordList(@js(['rows' => $rows, 'fields' => $fields]))">
    <input type="hidden" name="{{ $name }}[_][label]" value="">
    <div class="hidden gap-2 px-1 text-xs font-semibold text-slate-600 md:grid" style="grid-template-columns: repeat({{ count($group->columns) }}, minmax(0, 1fr)) 7rem">
        @foreach ($group->columns as $column)<span>{{ $column['label'] }}</span>@endforeach
        <span></span>
    </div>
    <ul class="mt-1 space-y-2">
        <template x-for="(row, index) in rows" :key="index">
            <li class="grid items-start gap-2 md:[grid-template-columns:var(--cols)]" style="--cols: repeat({{ count($group->columns) }}, minmax(0, 1fr)) 7rem">
                @foreach ($group->columns as $column)
                    @if ($column['type'] === 'key')
                        <input type="text" :name="'{{ $name }}[' + index + '][key]'" x-model="row.key" :readonly="row._saved" maxlength="40" autocomplete="off" placeholder="Schlüssel" @disabled(! $canEdit) aria-label="Schlüssel" class="font-mono {{ $field }}">
                    @elseif ($column['type'] === 'select')
                        <select :name="'{{ $name }}[' + index + '][{{ $column['field'] }}]'" x-model="row.{{ $column['field'] }}" @disabled(! $canEdit) aria-label="{{ $column['label'] }}" class="{{ $field }}">
                            <option value="">– keine –</option>
                            @foreach ($options[$column['options']] ?? [] as $optionKey => $optionLabel)
                                <option value="{{ $optionKey }}">{{ $optionLabel }} ({{ $optionKey }})</option>
                            @endforeach
                        </select>
                    @elseif ($column['type'] === 'status')
                        <div>
                            <input type="hidden" :name="'{{ $name }}[' + index + '][status]'" :value="row.status">
                            <template x-if="row.status === 'preparing' || row.key === 'unclear' || ! row._saved">
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-1.5 text-xs font-medium text-slate-700" x-text="row.key === 'unclear' ? 'aktiv (fest)' : (row._saved ? 'in Vorbereitung' : 'neu: in Vorbereitung')"></span>
                            </template>
                            <template x-if="row.status !== 'preparing' && row.key !== 'unclear' && row._saved">
                                <select x-model="row.status" @disabled(! $canEdit) aria-label="Status" class="{{ $field }}">
                                    <option value="active">aktiv</option>
                                    <option value="inactive">deaktiviert</option>
                                </select>
                            </template>
                        </div>
                    @else
                        <input type="text" :name="'{{ $name }}[' + index + '][{{ $column['field'] }}]'" x-model="row.{{ $column['field'] }}" maxlength="{{ $column['field'] === 'hint' ? 300 : 80 }}" autocomplete="off" placeholder="{{ $column['label'] }}" @disabled(! $canEdit) aria-label="{{ $column['label'] }}" class="{{ $field }}">
                    @endif
                @endforeach
                @if ($canEdit)
                    <div class="flex gap-1">
                        @if ($moves)
                            <button type="button" x-on:click="move(index, -1)" :disabled="index === 0" class="rounded-md px-2 py-1 text-xs ring-1 ring-slate-300 hover:bg-slate-50 disabled:opacity-40" aria-label="Nach oben">↑</button>
                            <button type="button" x-on:click="move(index, 1)" :disabled="index === rows.length - 1" class="rounded-md px-2 py-1 text-xs ring-1 ring-slate-300 hover:bg-slate-50 disabled:opacity-40" aria-label="Nach unten">↓</button>
                        @endif
                        @if (! $moves)
                            <button type="button" x-on:click="remove(index)" class="rounded-md px-2 py-1 text-xs font-semibold text-danger-700 ring-1 ring-danger-500/40 hover:bg-danger-500/10" :aria-label="'Eintrag ' + (index + 1) + ' entfernen'">Entfernen</button>
                        @endif
                    </div>
                @endif
            </li>
        </template>
    </ul>
    @if ($canEdit)
        <button type="button" x-on:click="add()" class="mt-3 inline-flex cursor-pointer items-center gap-1 text-sm font-semibold text-brand hover:text-brand-hover focus-visible:outline-2 focus-visible:outline-brand"><x-icon name="plus" size="16" /> Eintrag hinzufügen</button>
    @endif
</div>
