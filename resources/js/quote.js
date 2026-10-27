/**
 * The page "Angebot berechnen" (PROJ-34): discount calculator on the shop price, the offer and the costs of an
 * article. The formulas repeat App\Quotes\QuoteCalculator for the live display; saved offers are calculated on
 * the server.
 */
const eur = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
const percent = (value) => `${(Number(value) || 0).toLocaleString('de-DE', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} %`;
const num = (value) => Number(value) || 0;

export const blankOffer = (ticket = null) => ({
    id: null, number: null, created_on: null, valid_until: null,
    company: '', contact: '', email: '', street: '', city: '', note: '', items: [], ticket_number: ticket,
});

export default (config) => ({
    articles: config.articles,
    offers: config.offers,
    isAdmin: config.isAdmin,
    urls: config.urls,
    tab: 'calculator',
    search: '',
    articleId: config.articles[0]?.id ?? null,
    quantity: 30,
    discount: 10,
    customerGross: null,
    extraCosts: 0,
    extraShipping: 0,
    offer: blankOffer(config.ticket),
    message: '',
    problem: '',
    busy: false,
    costs: null,

    eur,
    percent,

    init() {
        this.syncCustomer();
    },

    get filtered() {
        const query = this.search.toLowerCase().trim();

        return this.articles.filter((article) => query === '' || `${article.sku} ${article.name} ${article.category ?? ''}`.toLowerCase().includes(query));
    },

    get article() {
        return this.articles.find((article) => article.id === Number(this.articleId)) ?? null;
    },

    unitCost(article) {
        return num(article.purchase_cost) + num(article.labor_minutes) / 60 * num(article.hour_rate)
            + num(article.machine_minutes) / 60 * num(article.machine_rate)
            + num(article.pack_cost) + num(article.ship_cost) + num(article.other_cost);
    },

    get result() {
        const article = this.article;

        if (!article) {
            return null;
        }

        const vat = num(article.vat_rate) / 100;
        const gross = num(this.customerGross);
        const cost = this.unitCost(article);
        const net = gross / (1 + vat);
        const quantity = Math.max(1, Math.floor(num(this.quantity)));
        const revenue = net * quantity;
        const costs = cost * quantity + num(this.extraCosts) + num(this.extraShipping);
        const profit = revenue - costs;
        const margin = revenue > 0 ? profit / revenue * 100 : 0;
        const target = num(article.target_margin);
        const targetGross = (1 - target / 100) > 0 ? cost / (1 - target / 100) * (1 + vat) : 0;
        const base = num(article.price);
        const state = !article.complete ? 'incomplete' : profit < 0 ? 'loss' : margin < target ? 'low' : 'good';

        return {
            gross, net, revenue, costs, profit, margin, state, target, targetGross,
            maxTarget: base > 0 ? Math.max(0, (1 - targetGross / base) * 100) : 0,
            maxBreakEven: base > 0 ? Math.max(0, (1 - cost * (1 + vat) / base) * 100) : 0,
            hint: this.hint(state, { profit, margin, target, targetGross, base, cost, vat }),
        };
    },

    hint(state, { profit, margin, target, targetGross, base, cost, vat }) {
        if (state === 'incomplete') {
            return 'Für diesen Artikel fehlen Einstandspreis oder Maschinenzeit. Ein Admin trägt sie unter „Kosten“ ein; bis dahin gibt es keine Margenbewertung.';
        }
        if (state === 'loss') {
            return `Verlust im Auftrag: ${eur.format(Math.abs(profit))}.`;
        }
        if (state === 'low') {
            const max = base > 0 ? Math.max(0, (1 - targetGross / base) * 100) : 0;

            return `Marge ${percent(margin)} liegt unter der Zielmarge ${percent(target)}. Maximal sinnvoller Rabatt bei Zielmarge: ${percent(max)}.`;
        }

        return `Rabatt liegt innerhalb der Zielmarge. Deckungsbeitrag Auftrag: ${eur.format(profit)}.`;
    },

    selectArticle() {
        this.discount = this.discount ?? 10;
        this.syncCustomer();
    },

    syncCustomer() {
        const article = this.article;
        this.customerGross = article ? Number((num(article.price) * (1 - num(this.discount) / 100)).toFixed(2)) : null;
    },

    fromDiscount() {
        this.syncCustomer();
    },

    fromPrice() {
        const base = num(this.article?.price);
        this.discount = base > 0 ? Number(Math.max(0, Math.min(100, (1 - num(this.customerGross) / base) * 100)).toFixed(2)) : 0;
    },

    addToOffer() {
        const article = this.article;

        if (!article) {
            return;
        }

        this.offer.items.push({
            sku: article.sku, name: article.name, qty: Math.max(1, Math.floor(num(this.quantity))),
            discount_pct: num(this.discount), unit_gross: num(this.customerGross), vat: num(article.vat_rate),
        });
        this.message = `${article.name} wurde zum Angebot hinzugefügt.`;
        this.problem = '';
        this.tab = 'offer';
    },

    removeItem(index) {
        this.offer.items.splice(index, 1);
    },

    get totals() {
        let gross = 0;
        let net = 0;

        this.offer.items.forEach((item) => {
            const line = Math.round(num(item.unit_gross) * 100) / 100 * Math.max(1, Math.floor(num(item.qty)));
            gross += line;
            net += line / (1 + num(item.vat) / 100);
        });

        return { gross, net, vat: gross - net };
    },

    async request(method, url, body) {
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: body === undefined ? undefined : JSON.stringify(body),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const first = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(first ?? data.message ?? (response.status === 403 ? 'Das dürfen nur Admins.' : `Fehler (${response.status}).`));
        }

        return data;
    },

    async saveOffer() {
        if (this.busy) {
            return;
        }

        this.busy = true;
        this.problem = '';
        this.message = '';

        try {
            const data = this.offer.id
                ? await this.request('PUT', this.urls.offer.replace('__ID__', this.offer.id), this.offer)
                : await this.request('POST', this.urls.offers, this.offer);
            this.offer = { ...blankOffer(), ...data };
            this.offers = [data, ...this.offers.filter((offer) => offer.id !== data.id)];
            this.message = `Angebot ${data.number} gespeichert.`;
        } catch (error) {
            this.problem = error.message;
        } finally {
            this.busy = false;
        }
    },

    openOffer(offer) {
        this.offer = JSON.parse(JSON.stringify({ ...blankOffer(), ...offer }));
        this.tab = 'offer';
        this.message = '';
        this.problem = '';
    },

    newOffer() {
        if (this.offer.items.length && !this.offer.id && !confirm('Neues Angebot beginnen? Nicht gespeicherte Änderungen am aktuellen Angebot gehen verloren.')) {
            return;
        }

        this.offer = blankOffer(this.offer.ticket_number);
        this.message = '';
        this.problem = '';
    },

    async deleteOffer(offer) {
        if (!confirm(`Angebot ${offer.number} wirklich löschen?`)) {
            return;
        }

        try {
            await this.request('DELETE', this.urls.offer.replace('__ID__', offer.id));
            this.offers = this.offers.filter((other) => other.id !== offer.id);

            if (this.offer.id === offer.id) {
                this.offer = blankOffer(this.offer.ticket_number);
            }
        } catch (error) {
            this.problem = error.message;
        }
    },

    // Costs (admins change them, everyone sees them).
    editCosts() {
        const article = this.article;

        if (!article) {
            return;
        }

        this.costs = JSON.parse(JSON.stringify({
            raw_articles: article.raw_articles.length ? article.raw_articles : [{ id: '', qty: 1, unit_cost: '' }],
            machine_minutes: article.machine_minutes ?? '', labor_minutes: article.labor_minutes, hour_rate: article.hour_rate,
            machine_rate: article.machine_rate, pack_cost: article.pack_cost, ship_cost: article.ship_cost, other_cost: article.other_cost,
            target_margin: article.target_margin, vat_rate: article.vat_rate,
        }));
    },

    get costsPurchase() {
        return (this.costs?.raw_articles ?? []).reduce((sum, row) => sum + num(row.qty) * num(row.unit_cost), 0);
    },

    async saveCosts() {
        if (this.busy || !this.costs) {
            return;
        }

        this.busy = true;
        this.problem = '';
        this.message = '';

        try {
            const body = { ...this.costs, machine_minutes: this.costs.machine_minutes === '' ? null : this.costs.machine_minutes };
            const data = await this.request('PUT', this.urls.costs.replace('__ID__', this.article.id), body);
            this.articles = this.articles.map((article) => (article.id === data.id ? data : article));
            this.costs = null;
            this.message = 'Kosten gespeichert.';
        } catch (error) {
            this.problem = error.message;
        } finally {
            this.busy = false;
        }
    },
});
