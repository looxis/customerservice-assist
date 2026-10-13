@props(['analysis', 'chosenProducts' => []])

{{-- Customer group and products: the scope of the knowledge (PROJ-4). Shared by the ticket form and the question form (PROJ-40). --}}
            <div class="grid gap-4 md:grid-cols-2">
                <x-select name="kundengruppe" label="Kundengruppe" :hint="$analysis['groupSource'] ? 'Vorbelegt: '.$analysis['groupSource'] : null" required>
                    @foreach ($analysis['groups'] as $group)
                        <option value="{{ $group->key }}" @selected(old('kundengruppe', $analysis['suggestedGroup']) === $group->key)>{{ $group->label }}</option>
                    @endforeach
                </x-select>

                <fieldset>
                    <legend class="block text-sm font-medium text-slate-900">Produkt(e)</legend>
                    @if ($analysis['products'] === [])
                        <p class="mt-1 text-sm text-slate-600">Noch kein Produktwissen hinterlegt – Vorschlag ohne Produktbezug.</p>
                    @else
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-2">
                            @foreach ($analysis['products'] as $product)
                                <label class="inline-flex min-h-9 items-center gap-2 text-sm text-slate-900">
                                    <input type="checkbox" name="produkte[]" value="{{ $product['slug'] }}" @checked(in_array($product['slug'], (array) $chosenProducts, true)) class="size-4 rounded border-slate-300 text-brand focus:ring-brand">
                                    {{ $product['title'] }}
                                    @if (($analysis['productReasons'][$product['slug']] ?? []) !== [])
                                        <span class="text-xs text-slate-600">({{ implode(' · ', $analysis['productReasons'][$product['slug']]) }})</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @foreach ($analysis['mentionedProducts'] ?? [] as $mentioned)
                            <p class="mt-1 text-xs font-medium text-warning-700">Im Ticket erwähnt: {{ collect($analysis['products'])->firstWhere('slug', $mentioned)['title'] ?? $mentioned }} ({{ implode(' · ', $analysis['productReasons'][$mentioned] ?? []) }}) – nicht ausgewählt</p>
                        @endforeach
                        <p class="mt-1 text-xs text-slate-600">Nichts angehakt = kein Produktbezug / unklar.</p>
                    @endif
                </fieldset>
            </div>
