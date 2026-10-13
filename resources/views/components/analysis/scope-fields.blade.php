@props(['analysis', 'chosenProducts' => [], 'noProduct' => false, 'unknownName' => ''])

@php
    $products = collect($analysis['products'])->map(fn (array $product): array => [
        'slug' => $product['slug'],
        'name' => $product['name'] ?? $product['title'],
        'title' => $product['title'],
        'terms' => $product['terms'] ?? [],
    ])->values()->all();
    $titles = collect($analysis['products'])->mapWithKeys(fn (array $product): array => [$product['slug'] => $product['name'] ?? $product['title']]);
    $reasons = $analysis['productReasons'] ?? [];
    $mentioned = array_map(fn (string $slug): string => '„'.($titles[$slug] ?? $slug).'“', $analysis['mentionedProducts'] ?? []);
    $config = [
        'products' => $products,
        'selected' => array_values(array_intersect((array) $chosenProducts, array_column($products, 'slug'))),
        'reasons' => $reasons,
        'mentioned' => $mentioned,
        'unknown' => (string) $unknownName,
        'none' => (bool) $noProduct,
        'group' => (string) old('kundengruppe', $analysis['suggestedGroup'] ?? ''),
    ];
@endphp

{{-- The two required answers of a proposal (PROJ-42): customer group and product. Shared by the ticket form and the question form. --}}
<div x-data="scopeFields(@js($config))" class="grid gap-4 md:grid-cols-2">
    <div>
        <x-select name="kundengruppe" label="Kundengruppe" :hint="($analysis['groupSource'] ?? null) ? 'Vorbelegt: '.$analysis['groupSource'] : null" required x-model="group">
            <option value="" @selected(old('kundengruppe', $analysis['suggestedGroup'] ?? '') === '')>Bitte wählen</option>
            @foreach ($analysis['groups'] as $group)
                <option value="{{ $group->key }}" @selected(old('kundengruppe', $analysis['suggestedGroup'] ?? '') === $group->key)>{{ $group->label }}</option>
            @endforeach
        </x-select>
        <p x-show="groupUnclear" x-cloak class="mt-1 text-xs font-medium text-warning-700">Bei „Noch unklar“ gelten nur Regeln für alle Kundengruppen. Passt die Bestellung oder der Kanal, bitte die Gruppe wählen.</p>
    </div>

    <fieldset class="min-w-0" aria-describedby="produkt-hinweis">
        <legend class="block text-sm font-medium text-slate-900">Produkt(e)<span class="text-danger-500" aria-hidden="true"> *</span></legend>

        <div class="mt-1 flex flex-wrap gap-2" x-show="selected.length > 0 || unknown !== ''">
            <template x-for="slug in selected" :key="slug">
                <span class="inline-flex max-w-full items-center gap-1 rounded-full bg-brand/10 py-1 pr-1 pl-3 text-sm text-brand-700">
                    <input type="hidden" name="produkte[]" :value="slug">
                    <span class="truncate" x-text="product(slug)?.name ?? slug"></span>
                    <span class="text-xs text-slate-600" x-show="(reasons[slug] ?? []).length > 0" x-text="'(' + (reasons[slug] ?? []).join(' · ') + ')'"></span>
                    <button type="button" class="inline-flex size-6 cursor-pointer items-center justify-center rounded-full hover:bg-brand/20 focus-visible:outline-2 focus-visible:outline-brand" x-on:click="remove(slug)" :aria-label="'Produkt ' + (product(slug)?.name ?? slug) + ' entfernen'">×</button>
                </span>
            </template>
            <span x-show="unknown !== ''" x-cloak class="inline-flex max-w-full items-center gap-1 rounded-full bg-warning-500/15 py-1 pr-1 pl-3 text-sm text-warning-700">
                <input type="hidden" name="produkt_ohne_wissen" :value="unknown">
                <span class="truncate" x-text="unknown"></span>
                <span class="text-xs">(kein Wissen hinterlegt)</span>
                <button type="button" class="inline-flex size-6 cursor-pointer items-center justify-center rounded-full hover:bg-warning-500/20 focus-visible:outline-2 focus-visible:outline-brand" x-on:click="unknown = ''" aria-label="Produkt ohne hinterlegtes Wissen entfernen">×</button>
            </span>
        </div>

        <div class="relative mt-2" x-on:click.outside="open = false">
            <input type="text" x-model="query" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="produkt-treffer" :aria-expanded="open.toString()"
                   placeholder="Produkt suchen, z. B. Glasfoto, Tasse, Lunchbox …"
                   x-on:focus="open = true" x-on:input="open = true; active = 0"
                   x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
                   x-on:keydown.enter="if (open && choices > 0) { $event.preventDefault(); choose(active) }" x-on:keydown.escape="open = false"
                   class="block w-full rounded-md border-0 bg-white px-3 py-1.5 text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-inset focus:ring-brand sm:text-sm">
            <ul id="produkt-treffer" role="listbox" x-show="open && choices > 0" x-cloak
                class="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-md bg-white py-1 text-sm shadow-3 ring-1 ring-slate-200">
                <template x-for="(product, index) in matches" :key="product.slug">
                    <li role="option" :aria-selected="(index === active).toString()" x-on:mousedown.prevent="add(product.slug)" x-on:mousemove="active = index"
                        class="cursor-pointer px-3 py-2" :class="index === active ? 'bg-brand/10' : ''">
                        <span class="font-medium text-slate-900" x-text="product.name"></span>
                        <span class="block truncate text-xs text-slate-600" x-show="product.title !== product.name" x-text="product.title"></span>
                    </li>
                </template>
                <li role="option" x-show="canAddUnknown" x-on:mousedown.prevent="addUnknown()" x-on:mousemove="active = matches.length" :aria-selected="(active === matches.length).toString()"
                    class="cursor-pointer border-t border-slate-100 px-3 py-2 text-warning-700" :class="active === matches.length ? 'bg-warning-500/10' : ''">
                    <span x-text="'„' + query.trim() + '“ als Produkt ohne hinterlegtes Wissen verwenden'"></span>
                </li>
            </ul>
        </div>

        <label class="mt-3 flex items-start gap-2 text-sm text-slate-900">
            <input type="checkbox" name="kein_produktbezug" value="1" x-model="none" x-on:change="toggleNone()" class="mt-0.5 size-4 cursor-pointer rounded border-slate-300 text-brand focus:ring-brand">
            <span>Kein Produktbezug <span class="text-slate-600">(z. B. Sendungsverfolgung oder eine allgemeine Frage)</span></span>
        </label>

        @foreach (($analysis['mentionedProducts'] ?? []) as $mentionedSlug)
            <p class="mt-1 text-xs font-medium text-warning-700">Im Ticket erwähnt: {{ $titles[$mentionedSlug] ?? $mentionedSlug }} ({{ implode(' · ', $reasons[$mentionedSlug] ?? []) }}) – nicht ausgewählt</p>
        @endforeach
        <p x-show="unknown !== ''" x-cloak class="mt-1 text-xs font-medium text-warning-700">Zu diesem Produkt ist noch kein Wissen hinterlegt. Bitte nach dem Vorschlag eine Wissenslücke melden; die App erfindet nichts dazu.</p>
        <p x-show="withoutProduct" x-cloak class="mt-1 text-xs font-medium text-warning-700">Ohne Produkt wird kein Produktwissen berücksichtigt. Geht es doch um ein Produkt, wähle es oder gib es oben ein – auch wenn es noch nicht in der Liste steht.</p>
        <p id="produkt-hinweis" class="mt-1 text-xs text-slate-600" x-show="selected.length === 0 && unknown === '' && !none">Bitte ein Produkt wählen, ein unbekanntes Produkt eingeben oder „Kein Produktbezug“ ankreuzen.</p>
        @error('produkte')<p class="mt-1 text-sm text-danger-500">{{ $message }}</p>@enderror
        @error('kein_produktbezug')<p class="mt-1 text-sm text-danger-500">{{ $message }}</p>@enderror
        @error('produkt_ohne_wissen')<p class="mt-1 text-sm text-danger-500">{{ $message }}</p>@enderror
    </fieldset>
</div>
