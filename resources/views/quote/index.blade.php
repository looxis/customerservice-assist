@php
    $input = 'block w-full rounded-md border-0 bg-white px-3 py-1.5 text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm';
    $label = 'block text-sm font-medium text-slate-900';
    $tabs = ['calculator' => 'Rechner', 'offer' => 'Angebot', 'saved' => 'Gespeicherte Angebote', 'costs' => 'Kosten'];
@endphp

<x-layouts.app title="Angebot berechnen" width="6xl">
    <div
        x-data="quote(@js([
            'articles' => $articles,
            'offers' => $offers,
            'isAdmin' => $isAdmin,
            'ticket' => $ticketNumber,
            'urls' => [
                'offers' => route('quote.offers.store'),
                'offer' => route('quote.offers.update', ['offer' => '__ID__']),
                'costs' => route('quote.articles.costs', ['article' => '__ID__']),
            ],
        ]))"
        class="space-y-6"
    >
        <x-card>
            <h2 class="text-base font-semibold text-slate-900">Angebot berechnen</h2>
            <p class="mt-1 text-sm text-slate-600">Rabatt auf den Shop-Preis rechnen, Marge und Rabattgrenze prüfen und daraus ein Angebot erstellen. Die Kosten sind intern, nicht an Kunden weitergeben.@if ($ticketNumber) Angebot zu <a href="{{ route('tickets.show', ['number' => $ticketNumber]) }}" class="font-medium text-brand underline">Ticket {{ $ticketNumber }}</a>.@endif</p>
        </x-card>

        <div class="flex flex-wrap gap-2" role="tablist" aria-label="Bereiche">
            @foreach ($tabs as $key => $name)
                <button type="button" role="tab" x-on:click="tab = '{{ $key }}'" x-bind:aria-selected="tab === '{{ $key }}'"
                    x-bind:class="tab === '{{ $key }}' ? 'bg-brand-tint text-brand-700 ring-brand' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50'"
                    class="min-h-11 rounded-md px-4 py-2 text-sm font-semibold ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-brand">{{ $name }}</button>
            @endforeach
        </div>

        <p x-show="message" x-text="message" class="rounded-md bg-success-500/10 p-3 text-sm font-medium text-success-700 ring-1 ring-inset ring-success-500/20" role="status"></p>
        <p x-show="problem" x-text="problem" class="rounded-md bg-danger-500/10 p-3 text-sm font-medium text-danger-700 ring-1 ring-inset ring-danger-500/20" role="alert"></p>

        {{-- Article picker, shared by "Rechner" and "Kosten" --}}
        <x-card x-show="tab === 'calculator' || tab === 'costs'">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="q-search" class="{{ $label }}">Artikel suchen</label>
                    <input id="q-search" type="search" x-model="search" placeholder="Artikel, SKU oder Kategorie …" autocomplete="off" class="{{ $input }} mt-1">
                </div>
                <div>
                    <label for="q-article" class="{{ $label }}">Artikel</label>
                    <select id="q-article" x-model="articleId" x-on:change="selectArticle(); costs = null" class="{{ $input }} mt-1">
                        <template x-for="item in filtered" :key="item.id"><option :value="item.id" :selected="item.id === Number(articleId)" x-text="item.sku + ' · ' + item.name"></option></template>
                    </select>
                    <p x-show="filtered.length === 0" class="mt-1 text-sm text-slate-600">Kein Artikel gefunden.</p>
                </div>
            </div>
            <p x-show="article" class="mt-3 text-sm text-slate-600">Shop-Preis <span class="font-semibold text-slate-900" x-text="article && eur.format(article.price)"></span> brutto · <span x-text="article?.category"></span></p>
        </x-card>

        {{-- Rechner --}}
        <div x-show="tab === 'calculator'" class="grid gap-6 lg:grid-cols-2">
            <x-card>
                <h3 class="text-base font-semibold text-slate-900">Rabatt vom Shop-Preis</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><label for="q-qty" class="{{ $label }}">Menge</label><input id="q-qty" type="number" min="1" step="1" x-model.number="quantity" class="{{ $input }} mt-1"></div>
                    <div><label for="q-disc" class="{{ $label }}">Rabatt %</label><input id="q-disc" type="number" min="0" max="100" step="0.1" x-model.number="discount" x-on:input="fromDiscount()" class="{{ $input }} mt-1"></div>
                    <div><label for="q-price" class="{{ $label }}">Kundenpreis brutto / Stück</label><input id="q-price" type="number" min="0" step="0.01" x-model.number="customerGross" x-on:input="fromPrice()" class="{{ $input }} mt-1"></div>
                    <div><label for="q-extra" class="{{ $label }}">Zusatzkosten Auftrag €</label><input id="q-extra" type="number" min="0" step="0.01" x-model.number="extraCosts" class="{{ $input }} mt-1"></div>
                    <div><label for="q-ship" class="{{ $label }}">Zusatzversand Auftrag €</label><input id="q-ship" type="number" min="0" step="0.01" x-model.number="extraShipping" class="{{ $input }} mt-1"></div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2" aria-label="Rabatt schnell wählen">
                    <template x-for="value in [5, 10, 15, 20, 25, 30, 40]" :key="value">
                        <button type="button" x-on:click="discount = value; fromDiscount()" class="min-h-9 rounded-pill px-3 text-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50" x-text="value + ' %'"></button>
                    </template>
                </div>
                <div class="mt-4"><x-button type="button" x-on:click="addToOffer()" x-bind:disabled="! article">+ Artikel zum Angebot</x-button></div>
            </x-card>

            <x-card>
                <h3 class="text-base font-semibold text-slate-900">Ergebnis</h3>
                <template x-if="result">
                    <div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-md bg-brand-tint p-3"><dt class="text-slate-600">Kundenpreis brutto</dt><dd class="text-lg font-semibold" x-text="eur.format(result.gross)"></dd></div>
                            <div class="rounded-md bg-slate-50 p-3"><dt class="text-slate-600">Kundenpreis netto</dt><dd class="text-lg font-semibold" x-text="eur.format(result.net)"></dd></div>
                            <div class="rounded-md bg-slate-50 p-3"><dt class="text-slate-600">Auftragswert netto</dt><dd class="text-lg font-semibold" x-text="eur.format(result.revenue)"></dd></div>
                            <div class="rounded-md bg-slate-50 p-3"><dt class="text-slate-600">Gesamtkosten Auftrag</dt><dd class="text-lg font-semibold" x-text="article.complete ? eur.format(result.costs) : '–'"></dd></div>
                            <div class="rounded-md bg-slate-50 p-3"><dt class="text-slate-600">Deckungsbeitrag</dt><dd class="text-lg font-semibold" x-text="article.complete ? eur.format(result.profit) : '–'"></dd></div>
                            <div class="rounded-md p-3" x-bind:class="{ 'bg-success-500/10': result.state === 'good', 'bg-warning-500/10': result.state === 'low', 'bg-danger-500/10': result.state === 'loss', 'bg-slate-50': result.state === 'incomplete' }"><dt class="text-slate-600">Marge</dt><dd class="text-lg font-semibold" x-text="article.complete ? percent(result.margin) : '–'"></dd></div>
                        </dl>
                        <div class="mt-3 divide-y divide-slate-200 text-sm">
                            <p class="flex justify-between py-2"><span class="text-slate-600">Max. Rabatt bei Zielmarge (<span x-text="percent(result.target)"></span>)</span><strong x-text="article.complete ? percent(result.maxTarget) : '–'"></strong></p>
                            <p class="flex justify-between py-2"><span class="text-slate-600">Max. Rabatt bis Break-even</span><strong x-text="article.complete ? percent(result.maxBreakEven) : '–'"></strong></p>
                        </div>
                        <p class="mt-3 rounded-md p-3 text-sm font-medium ring-1 ring-inset" x-text="result.hint"
                            x-bind:class="{ 'bg-success-500/10 text-success-700 ring-success-500/20': result.state === 'good', 'bg-warning-500/10 text-warning-700 ring-warning-500/20': result.state === 'low' || result.state === 'incomplete', 'bg-danger-500/10 text-danger-700 ring-danger-500/20': result.state === 'loss' }"></p>
                    </div>
                </template>
                <p x-show="! result" class="mt-3 text-sm text-slate-600">Bitte einen Artikel wählen.</p>
            </x-card>
        </div>

        {{-- Angebot --}}
        <x-card x-show="tab === 'offer'">
            <h3 class="text-base font-semibold text-slate-900">Angebot erstellen</h3>
            <p class="mt-1 text-sm text-slate-600">Artikel kommen aus dem Rechner. Das Angebot ist ab dem Speichern sieben Tage gültig; die Angebotsnummer wird beim Speichern vergeben.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label for="o-company" class="{{ $label }}">Firma</label><input id="o-company" type="text" maxlength="200" x-model="offer.company" class="{{ $input }} mt-1"></div>
                <div><label for="o-contact" class="{{ $label }}">Ansprechpartner</label><input id="o-contact" type="text" maxlength="200" x-model="offer.contact" class="{{ $input }} mt-1"></div>
                <div><label for="o-email" class="{{ $label }}">E-Mail</label><input id="o-email" type="email" maxlength="200" x-model="offer.email" class="{{ $input }} mt-1"></div>
                <div><label for="o-street" class="{{ $label }}">Straße / Hausnummer</label><input id="o-street" type="text" maxlength="200" x-model="offer.street" class="{{ $input }} mt-1"></div>
                <div><label for="o-city" class="{{ $label }}">PLZ / Ort</label><input id="o-city" type="text" maxlength="200" x-model="offer.city" class="{{ $input }} mt-1"></div>
                <div x-show="offer.number"><span class="{{ $label }}">Angebotsnummer</span><p class="mt-2 font-mono text-sm" x-text="offer.number"></p></div>
            </div>
            <div class="mt-4"><label for="o-note" class="{{ $label }}">Bemerkung</label><textarea id="o-note" rows="3" maxlength="4000" x-model="offer.note" placeholder="z. B. Lieferzeit, Motivhinweis oder Projektbezug" class="{{ $input }} mt-1"></textarea></div>

            <div class="mt-4 overflow-x-auto rounded-md ring-1 ring-slate-200">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600"><tr><th class="px-3 py-2">SKU</th><th class="px-3 py-2">Artikel</th><th class="px-3 py-2">Menge</th><th class="px-3 py-2">Rabatt</th><th class="px-3 py-2 text-right">Stück brutto</th><th class="px-3 py-2 text-right">Gesamt brutto</th><th class="px-3 py-2"><span class="sr-only">Entfernen</span></th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        <template x-for="(item, index) in offer.items" :key="index">
                            <tr>
                                <td class="px-3 py-2 font-mono" x-text="item.sku"></td>
                                <td class="px-3 py-2" x-text="item.name"></td>
                                <td class="px-3 py-2"><input type="number" min="1" step="1" x-model.number="item.qty" class="{{ $input }} w-24" x-bind:aria-label="'Menge ' + item.name"></td>
                                <td class="px-3 py-2" x-text="percent(item.discount_pct)"></td>
                                <td class="px-3 py-2 text-right" x-text="eur.format(item.unit_gross)"></td>
                                <td class="px-3 py-2 text-right font-semibold" x-text="eur.format(item.unit_gross * item.qty)"></td>
                                <td class="px-3 py-2 text-right"><button type="button" class="font-medium text-brand underline" x-on:click="removeItem(index)">Entfernen</button></td>
                            </tr>
                        </template>
                        <tr x-show="offer.items.length === 0"><td colspan="7" class="px-3 py-4 text-slate-600">Noch keine Artikel im Angebot. Im Rechner „+ Artikel zum Angebot“ wählen.</td></tr>
                    </tbody>
                </table>
            </div>

            <dl class="mt-4 ml-auto max-w-sm space-y-1 rounded-md bg-slate-50 p-3 text-sm">
                <div class="flex justify-between"><dt>Netto</dt><dd class="font-semibold" x-text="eur.format(totals.net)"></dd></div>
                <div class="flex justify-between"><dt>MwSt.</dt><dd class="font-semibold" x-text="eur.format(totals.vat)"></dd></div>
                <div class="flex justify-between border-t border-slate-300 pt-1 text-base"><dt>Gesamt brutto</dt><dd class="font-semibold" x-text="eur.format(totals.gross)"></dd></div>
            </dl>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-button type="button" x-on:click="saveOffer()" x-bind:disabled="busy || offer.items.length === 0">Angebot speichern</x-button>
                <a x-show="offer.id" x-bind:href="offers.find((o) => o.id === offer.id)?.print_url" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Drucken / PDF</a>
                <x-button type="button" variant="secondary" x-on:click="newOffer()">Neues Angebot</x-button>
            </div>
        </x-card>

        {{-- Gespeicherte Angebote --}}
        <x-card x-show="tab === 'saved'">
            <h3 class="text-base font-semibold text-slate-900">Gespeicherte Angebote</h3>
            <p x-show="offers.length === 0" class="mt-3 text-sm text-slate-600">Noch keine Angebote gespeichert.</p>
            <ul class="mt-3 divide-y divide-slate-200" aria-label="Gespeicherte Angebote">
                <template x-for="saved in offers" :key="saved.id">
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900"><span x-text="saved.number"></span> · <span x-text="saved.company || saved.contact || 'ohne Kundenname'"></span></p>
                            <p class="text-slate-600"><span x-text="saved.created_on.split('-').reverse().join('.')"></span> · gültig bis <span x-text="saved.valid_until.split('-').reverse().join('.')"></span> · <span x-text="saved.items.length"></span> Position(en) · <span x-text="eur.format(saved.gross)"></span> · <span x-text="saved.created_by"></span><template x-if="saved.ticket_number"><span> · Ticket <span x-text="saved.ticket_number"></span></span></template></p>
                        </div>
                        <div class="flex gap-2">
                            <x-button type="button" variant="secondary" x-on:click="openOffer(saved)">Öffnen</x-button>
                            <a x-bind:href="saved.print_url" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">PDF</a>
                            <x-button type="button" variant="danger" x-on:click="deleteOffer(saved)">Löschen</x-button>
                        </div>
                    </li>
                </template>
            </ul>
        </x-card>

        {{-- Kosten --}}
        <x-card x-show="tab === 'costs'">
            <h3 class="text-base font-semibold text-slate-900">Interne Kosten des Artikels</h3>
            <template x-if="article && ! costs">
                <div>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                        <div class="rounded-md bg-slate-50 p-3"><dt class="text-slate-600">Einstand netto</dt><dd class="font-semibold" x-text="article.purchase_cost === null ? 'fehlt' : eur.format(article.purchase_cost)"></dd></div>
                        <div class="rounded-md bg-slate-50 p-3"><dt class="text-slate-600">Maschinenzeit</dt><dd class="font-semibold" x-text="article.machine_minutes === null ? 'fehlt' : article.machine_minutes + ' min'"></dd></div>
                        <div class="rounded-md bg-slate-50 p-3"><dt class="text-slate-600">Kosten je Stück gesamt</dt><dd class="font-semibold" x-text="article.complete ? eur.format(unitCost(article)) : 'unvollständig'"></dd></div>
                    </dl>
                    <p class="mt-3 text-sm text-slate-600">Arbeitszeit <span x-text="article.labor_minutes"></span> min × <span x-text="eur.format(article.hour_rate)"></span>/h · Maschine <span x-text="eur.format(article.machine_rate)"></span>/h · Zielmarge <span x-text="percent(article.target_margin)"></span> · MwSt. <span x-text="percent(article.vat_rate)"></span></p>
                    <ul class="mt-3 text-sm text-slate-700">
                        <template x-for="row in article.raw_articles" :key="row.id + row.qty"><li><span class="font-mono" x-text="row.id"></span> · <span x-text="row.qty"></span> × <span x-text="eur.format(row.unit_cost)"></span></li></template>
                    </ul>
                    <div class="mt-4">
                        <x-button type="button" x-show="isAdmin" x-on:click="editCosts()">Kosten bearbeiten</x-button>
                        <p x-show="! isAdmin" class="text-sm text-slate-600">Kosten ändern dürfen nur Admins.</p>
                    </div>
                </div>
            </template>

            <template x-if="costs">
                <form x-on:submit.prevent="saveCosts()" class="mt-4 space-y-4">
                    <fieldset>
                        <legend class="{{ $label }}">Grundartikel (Menge × Einstandspreis netto)</legend>
                        <template x-for="(row, index) in costs.raw_articles" :key="index">
                            <div class="mt-2 grid grid-cols-[1fr_5rem_7rem_auto] items-end gap-2">
                                <input type="text" x-model="row.id" placeholder="Grundartikel-Nr." aria-label="Grundartikelnummer" class="{{ $input }}">
                                <input type="number" min="0.01" step="0.01" x-model.number="row.qty" aria-label="Menge" class="{{ $input }}">
                                <input type="number" min="0" step="0.01" x-model.number="row.unit_cost" aria-label="Einstandspreis je Stück" placeholder="€ je Stück" class="{{ $input }}">
                                <button type="button" class="text-sm font-medium text-brand underline" x-on:click="costs.raw_articles.splice(index, 1)">Entfernen</button>
                            </div>
                        </template>
                        <button type="button" class="mt-2 text-sm font-medium text-brand underline" x-on:click="costs.raw_articles.push({ id: '', qty: 1, unit_cost: '' })">+ Grundartikel hinzufügen</button>
                        <p class="mt-2 text-sm text-slate-600">Einstand gesamt: <strong x-text="eur.format(costsPurchase)"></strong></p>
                    </fieldset>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div><label for="c-machine" class="{{ $label }}">Maschinenzeit Minuten</label><input id="c-machine" type="number" min="0" step="0.1" x-model="costs.machine_minutes" class="{{ $input }} mt-1"></div>
                        <div><label for="c-labor" class="{{ $label }}">Arbeitszeit Minuten</label><input id="c-labor" type="number" min="0" step="0.1" x-model.number="costs.labor_minutes" class="{{ $input }} mt-1"></div>
                        <div><label for="c-hour" class="{{ $label }}">Stundensatz Arbeit €/h</label><input id="c-hour" type="number" min="0" step="0.01" x-model.number="costs.hour_rate" class="{{ $input }} mt-1"></div>
                        <div><label for="c-mrate" class="{{ $label }}">Maschinenstundensatz €/h</label><input id="c-mrate" type="number" min="0" step="0.01" x-model.number="costs.machine_rate" class="{{ $input }} mt-1"></div>
                        <div><label for="c-pack" class="{{ $label }}">Verpackung €</label><input id="c-pack" type="number" min="0" step="0.01" x-model.number="costs.pack_cost" class="{{ $input }} mt-1"></div>
                        <div><label for="c-ship" class="{{ $label }}">Versandanteil €</label><input id="c-ship" type="number" min="0" step="0.01" x-model.number="costs.ship_cost" class="{{ $input }} mt-1"></div>
                        <div><label for="c-other" class="{{ $label }}">Sonstige Kosten €</label><input id="c-other" type="number" min="0" step="0.01" x-model.number="costs.other_cost" class="{{ $input }} mt-1"></div>
                        <div><label for="c-target" class="{{ $label }}">Zielmarge %</label><input id="c-target" type="number" min="0" max="90" step="0.1" x-model.number="costs.target_margin" class="{{ $input }} mt-1"></div>
                        <div><label for="c-vat" class="{{ $label }}">MwSt. %</label><input id="c-vat" type="number" min="0" max="100" step="0.1" x-model.number="costs.vat_rate" class="{{ $input }} mt-1"></div>
                    </div>
                    <div class="flex gap-2">
                        <x-button type="submit" x-bind:disabled="busy">Kosten speichern</x-button>
                        <x-button type="button" variant="secondary" x-on:click="costs = null">Abbrechen</x-button>
                    </div>
                </form>
            </template>
        </x-card>
    </div>
</x-layouts.app>
