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

    init() {
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
        }
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
        return this.none && this.selected.length === 0 && this.unknown === '';
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

        return parts.length === 0 ? null : `${parts.join('\n\n')}\n\nSicher, dass du so fortfahren willst?`;
    },
});
