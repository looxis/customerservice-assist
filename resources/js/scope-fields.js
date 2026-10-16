/**
 * Customer group and products of the proposal form (PROJ-42). The products are searched and picked as
 * chips (50 to 100 products do not fit a list of checkboxes); a product the knowledge base does not
 * know can be typed in, and "no product" must be chosen on purpose. The form asks once more before it
 * is sent with an unclear group or without a product.
 */
export default (config) => ({
    products: config.products,
    selected: [...config.selected],
    reasons: config.reasons,
    mentioned: config.mentioned,
    unknown: config.unknown,
    none: config.none,
    group: config.group,
    query: '',
    open: false,
    active: 0,

    // Product catalog (PROJ-38): the concrete product, as opposed to the area of rules above.
    forge: config.forge ?? { enabled: false, searchUrl: '', candidates: [], selected: [] },
    forgeList: [...(config.forge?.candidates ?? [])],
    forgeSelected: [...(config.forge?.selected ?? [])],
    forgeQuery: '',
    forgeHits: [],
    forgeOpen: false,
    forgeBusy: false,
    forgeMessage: '',
    forgeTimer: null,

    init() {
        if (this.forge.enabled) {
            this.forgeSelected.filter((id) => !this.forgeList.some((product) => product.id === id)).forEach((id) => this.forgeLoad(id));
            this.forgeSelected.forEach((id) => this.forgeSuggestAreas(this.forgeProduct(id)));
        }

        const form = this.$el.closest('form');

        // Before the handlers of the form: a declined question must stop the loading overlay too.
        form?.addEventListener('submit', (event) => {
            const message = this.confirmation();

            if (message !== null && !window.confirm(message)) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, { capture: true });
    },

    normalize(text) {
        return String(text).toLowerCase().replace(/ä/g, 'a').replace(/ö/g, 'o').replace(/ü/g, 'u').replace(/ß/g, 'ss');
    },

    get matches() {
        const query = this.normalize(this.query.trim());

        return this.products
            .filter((product) => !this.selected.includes(product.slug))
            .filter((product) => query === '' || this.normalize([product.name, product.title, product.slug, ...product.terms].join(' ')).includes(query))
            .slice(0, 8);
    },

    get canAddUnknown() {
        const query = this.query.trim();

        return query !== '' && !this.products.some((product) => this.normalize(product.name) === this.normalize(query));
    },

    get choices() {
        return this.matches.length + (this.canAddUnknown ? 1 : 0);
    },

    product(slug) {
        return this.products.find((product) => product.slug === slug);
    },

    add(slug) {
        if (!this.selected.includes(slug)) {
            this.selected.push(slug);
        }

        this.none = false;
        this.reset();
    },

    remove(slug) {
        this.selected = this.selected.filter((other) => other !== slug);
    },

    addUnknown() {
        this.unknown = this.query.trim().slice(0, 120);
        this.none = false;
        this.reset();
    },

    toggleNone() {
        if (this.none) {
            this.selected = [];
            this.unknown = '';
            this.forgeSelected = [];
        }
    },

    forgeProduct(id) {
        return this.forgeList.find((product) => product.id === id);
    },

    forgeIsSelected(id) {
        return this.forgeSelected.includes(id);
    },

    forgeToggle(id) {
        if (this.forgeIsSelected(id)) {
            this.forgeSelected = this.forgeSelected.filter((other) => other !== id);
        } else {
            this.forgeSelected.push(id);
            this.none = false;
            this.forgeSuggestAreas(this.forgeProduct(id));
        }
    },

    // The area of rules is proposed from the product, never forced; only filled while it is still empty.
    forgeSuggestAreas(product) {
        if (!product || this.selected.length > 0 || this.unknown !== '') {
            return;
        }

        (product.knowledge_slugs ?? []).filter((slug) => this.product(slug)).forEach((slug) => {
            this.selected.push(slug);
            this.reasons[slug] = [...(this.reasons[slug] ?? []), 'vom Katalogprodukt vorgeschlagen'];
        });
    },

    get forgeWithoutArea() {
        return this.forge.enabled && this.forgeSelected.length > 0 && this.selected.length === 0 && this.unknown === '';
    },

    get forgeUnchosen() {
        return this.forge.enabled && this.forgeList.length > 1 && this.forgeSelected.length === 0 && !this.none;
    },

    forgeSearch() {
        clearTimeout(this.forgeTimer);
        this.forgeMessage = '';

        if (this.forgeQuery.trim().length < 2) {
            this.forgeHits = [];

            return;
        }

        this.forgeTimer = setTimeout(async () => {
            this.forgeBusy = true;

            try {
                const answer = await this.forgeRequest({ q: this.forgeQuery.trim() });
                this.forgeHits = answer.hits ?? [];
                this.forgeMessage = answer.error ?? (this.forgeHits.length === 0 ? 'Kein Produkt gefunden.' : (answer.total > this.forgeHits.length ? `${answer.total} Treffer, die ersten ${this.forgeHits.length} werden gezeigt. Bitte genauer suchen.` : ''));
            } catch {
                this.forgeHits = [];
                this.forgeMessage = 'Die Produktsuche ist gerade nicht erreichbar.';
            } finally {
                this.forgeBusy = false;
            }
        }, 300);
    },

    async forgeRequest(params) {
        const response = await fetch(`${this.forge.searchUrl}?${new URLSearchParams(params)}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });

        return response.json();
    },

    async forgeAdd(hit) {
        if (!this.forgeProduct(hit.id)) {
            this.forgeList.push({ ...hit, source: 'Suche', sku_used: null, has_service_knowledge: null });
        }

        if (!this.forgeIsSelected(hit.id)) {
            this.forgeSelected.push(hit.id);
        }

        this.none = false;
        this.forgeQuery = '';
        this.forgeHits = [];
        this.forgeOpen = false;
        await this.forgeLoad(hit.id);
        this.forgeSuggestAreas(this.forgeProduct(hit.id));
    },

    // Details of one product (what exists, which area of rules fits); also restores a product chosen before.
    async forgeLoad(id) {
        try {
            const answer = await this.forgeRequest({ id });

            if (answer.product) {
                const index = this.forgeList.findIndex((product) => product.id === id);
                const entry = { ...answer.product, source: index >= 0 ? this.forgeList[index].source : 'zuvor gewählt', sku_used: index >= 0 ? this.forgeList[index].sku_used : null };
                index >= 0 ? this.forgeList.splice(index, 1, entry) : this.forgeList.push(entry);
            }
        } catch {
            // The choice stays; only the details are missing.
        }
    },

    forgeLabel(product) {
        const kind = { master: 'Master (alle Varianten)', variant: 'Variante', standalone: '' }[product.type] ?? '';
        const parts = [product.sku ? `SKU ${product.sku}` : null, kind || null, product.master ? `Master: ${product.master.name}` : null, product.status === 'inactive' ? 'inaktiv' : null];

        return parts.filter(Boolean).join(' · ');
    },

    reset() {
        this.query = '';
        this.open = false;
        this.active = 0;
    },

    choose(index) {
        if (index < this.matches.length) {
            this.add(this.matches[index].slug);
        } else if (this.canAddUnknown) {
            this.addUnknown();
        }
    },

    move(step) {
        this.open = true;
        this.active = this.choices === 0 ? 0 : (this.active + step + this.choices) % this.choices;
    },

    get groupUnclear() {
        return this.group === 'unclear';
    },

    get withoutProduct() {
        return this.none && this.selected.length === 0 && this.unknown === '' && this.forgeSelected.length === 0;
    },

    confirmation() {
        const parts = [];

        if (this.groupUnclear) {
            parts.push('Die Kundengruppe ist „Noch unklar“. Dann gelten nur Regeln, die für alle Kundengruppen gelten.');
        }

        if (this.withoutProduct) {
            parts.push(this.mentioned.length > 0
                ? `„Kein Produktbezug“ ist gewählt, im Ticket wird aber ${this.mentioned.join(', ')} erwähnt. Dann wird kein Produktwissen berücksichtigt.`
                : '„Kein Produktbezug“ ist gewählt. Dann wird kein Produktwissen berücksichtigt.');
        }

        if (this.forgeUnchosen) {
            parts.push('Die Bestellung enthält mehrere Produkte und keines ist als betroffen markiert. Dann gehen keine Produktinformationen aus dem Katalog an die KI.');
        }

        return parts.length === 0 ? null : `${parts.join('\n\n')}\n\nSicher, dass du so fortfahren willst?`;
    },
});
