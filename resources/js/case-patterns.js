/**
 * Guided fact-finding (PROJ-103). The store holds what the employee chose: the case patterns (hypotheses), the answers
 * to their check questions, the customer checks taken over as the next step, and "no pattern fits". The pattern
 * area and the hint in the guided processing both read it; the area knows no ticket, so the page "Frage stellen"
 * can use it later as well.
 */
export const caseFindingsStore = {
    patterns: [],
    selected: [],
    answers: {},
    adopted: [],
    none: false,
    notices: [],

    pattern(id) {
        return this.patterns.find((pattern) => pattern.id === id) ?? null;
    },

    isSelected(id) {
        return this.selected.includes(id);
    },

    /** The customer checks of the chosen patterns, one per pattern. */
    get customerChecks() {
        return this.patterns
            .filter((pattern) => this.selected.includes(pattern.id) && pattern.customer_check)
            .map((pattern) => ({ id: pattern.id, title: pattern.title, ...pattern.customer_check }));
    },

    isAdopted(id) {
        return this.adopted.includes(id);
    },

    adopt(id) {
        if (!this.adopted.includes(id) && this.customerChecks.some((check) => check.id === id)) {
            this.adopted.push(id);
        }
    },

    withdraw(id) {
        this.adopted = this.adopted.filter((other) => other !== id);
    },

    /** Removes a chosen pattern with its answers and its taken-over check; a reason makes it visible. */
    drop(id, title, reason) {
        const wasAdopted = this.adopted.includes(id);
        const check = this.pattern(id)?.customer_check;

        this.selected = this.selected.filter((other) => other !== id);
        this.adopted = this.adopted.filter((other) => other !== id);
        delete this.answers[id];

        if (reason) {
            this.notices.push(`„${title}“ abgewählt: ${reason}.`);

            if (wasAdopted) {
                this.notices.push(`Übernommene Prüfung „${check?.name ?? title}“ entfernt.`);
            }
        }
    },
};

export default (config) => ({
    url: config.url,
    category: config.category ?? null,
    known: config.known ?? {},
    scope: { group: config.group ?? '', channel: config.channel ?? '', products: [...(config.products ?? [])] },
    loading: false,
    failed: false,
    loaded: false,
    showGeneral: false,
    timer: null,

    init() {
        const store = this.$store.caseFindings;
        store.selected = [...(config.selected ?? [])];
        store.answers = JSON.parse(JSON.stringify(config.answers ?? {}));
        store.adopted = [...(config.adopted ?? [])];
        store.none = Boolean(config.none);
        store.notices = [];

        this.$watch('mode', () => this.schedule());
        this.schedule();
    },

    /** The scope fields announce every change of group, channel and products. */
    rescope(detail) {
        this.scope = { group: detail.group ?? '', channel: detail.channel ?? '', products: [...(detail.products ?? [])] };
        this.schedule();
    },

    schedule() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.load(), 150);
    },

    async load() {
        const store = this.$store.caseFindings;
        const params = new URLSearchParams();
        params.set('kundengruppe', this.scope.group);
        params.set('verkaufskanal', this.scope.channel);
        params.set('formular', typeof this.mode === 'string' ? this.mode : '');
        params.set('kategorie', this.category ?? '');
        this.scope.products.forEach((slug) => params.append('produkte[]', slug));
        store.selected.forEach((id) => params.append('gewaehlt[]', id));

        this.loading = true;
        this.failed = false;

        try {
            const response = await fetch(`${this.url}?${params.toString()}`, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            const data = await response.json();
            store.patterns = data.patterns ?? [];

            Object.entries(data.dropped ?? {}).forEach(([id, entry]) => store.drop(id, entry.title, entry.reason));

            // A taken-over check whose pattern no longer carries one is removed as well.
            store.adopted
                .filter((id) => !store.customerChecks.some((check) => check.id === id))
                .forEach((id) => {
                    store.withdraw(id);
                    store.notices.push(`Übernommene Prüfung zu „${store.pattern(id)?.title ?? id}“ entfernt: gilt nicht mehr.`);
                });

            this.loaded = true;
        } catch {
            this.failed = true;
        } finally {
            this.loading = false;
        }
    },

    get productPatterns() {
        return this.$store.caseFindings.patterns.filter((pattern) => pattern.for_product);
    },

    get generalPatterns() {
        return this.$store.caseFindings.patterns.filter((pattern) => !pattern.for_product);
    },

    /** The line of the closed area: how many patterns there are for the product and in general. */
    get summary() {
        if (!this.loaded) {
            return this.failed ? 'Fallmuster konnten nicht geladen werden.' : 'Fallmuster werden geladen …';
        }

        const products = [...new Set(this.productPatterns.flatMap((pattern) => pattern.products))];
        const parts = [];

        if (this.productPatterns.length > 0) {
            parts.push(`${this.productPatterns.length} ${this.productPatterns.length === 1 ? 'Fallmuster' : 'Fallmuster'}${products.length > 0 ? ` zu ${products.join(', ')}` : ' zum Produkt'}`);
        }

        if (this.generalPatterns.length > 0) {
            parts.push(`${this.generalPatterns.length} allgemeine`);
        }

        return parts.length === 0 ? 'keine Fallmuster hinterlegt' : parts.join(', ');
    },

    get empty() {
        return this.loaded && this.$store.caseFindings.patterns.length === 0;
    },

    toggle(pattern) {
        const store = this.$store.caseFindings;

        if (store.isSelected(pattern.id)) {
            store.drop(pattern.id, pattern.title, null);

            return;
        }

        store.selected.push(pattern.id);
        store.none = false;
    },

    toggleNone() {
        const store = this.$store.caseFindings;
        store.none = !store.none;

        if (store.none) {
            [...store.selected].forEach((id) => store.drop(id, id, null));
        }
    },

    answer(pattern, check) {
        return this.$store.caseFindings.answers[pattern.id]?.[check.key] ?? '';
    },

    setAnswer(pattern, check, value) {
        const store = this.$store.caseFindings;
        const current = { ...(store.answers[pattern.id] ?? {}) };

        if (current[check.key] === value) {
            delete current[check.key];
        } else {
            current[check.key] = value;
        }

        store.answers = { ...store.answers, [pattern.id]: current };
    },

    /**
     * How a check question shows: answered by the app (only a sure yes/no value), "checked during the analysis",
     * or asked, with what the app knows as a hint.
     */
    knowledgeOf(check) {
        if (check.auto) {
            return { state: 'auto', text: 'wird bei der Analyse automatisch geprüft' };
        }

        const value = check.known_from ? this.known[check.known_from] : null;

        if (!value) {
            return { state: 'ask', text: '' };
        }

        if (value.sure && value.type === 'bool') {
            return { state: 'known', text: `bereits bekannt: ${value.value ? 'ja' : 'nein'} (${value.source})` };
        }

        if (value.sure) {
            return { state: 'ask', text: `Hinweis: ${value.value} (${value.source})` };
        }

        return { state: 'ask', text: `Information fehlt: ${value.hint}` };
    },

    /** The answers as form fields; a question the app answered itself is not sent, the server knows it. */
    get answerFields() {
        const store = this.$store.caseFindings;
        const fields = [];

        store.selected.forEach((id) => {
            const pattern = store.pattern(id);

            Object.entries(store.answers[id] ?? {}).forEach(([key, value]) => {
                const check = pattern?.checks.find((item) => item.key === key);

                if (check && this.knowledgeOf(check).state === 'ask') {
                    fields.push({ name: `pruefung[${id}][${key}]`, value });
                }
            });
        });

        return fields;
    },
});
