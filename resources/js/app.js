import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * The staff name chosen in this browser (PROJ-5). The header and the hint on
 * "Ticket analysieren" read it, so both update together without a reload.
 */
Alpine.store('staff', {
    name: null,
    saving: false,
    failed: false,

    async select(form, name) {
        this.saving = true;
        this.failed = false;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: new URLSearchParams({ name }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            this.name = (await response.json()).name;

            return true;
        } catch {
            this.failed = true;

            return false;
        } finally {
            this.saving = false;
        }
    },
});

/**
 * Ticket input (PROJ-6): accepts what Zammad's copy button produces
 * ("Ticket#2137942") and reduces it to the bare number before sending.
 * The server checks the number again.
 */
Alpine.data('ticketLookup', (initial = '', initialError = '') => ({
    value: initial,
    error: initialError,
    busy: false,
    invalidMessage: 'Bitte eine Ticketnummer eingeben, z. B. Ticket#2137942.',

    normalize(raw) {
        const match = String(raw ?? '').match(/^\s*(?:ticket\s*)?#?\s*(\d{1,20})\s*$/i);

        return match ? match[1] : null;
    },

    submit(event) {
        const number = this.normalize(this.value);

        if (this.busy || number === null) {
            event.preventDefault();
            this.error = number === null ? this.invalidMessage : '';

            return;
        }

        this.value = number;
        this.error = '';
        this.busy = true;
        this.$dispatch('loading-start', { title: 'Ticket wird geladen …' });
    },

    async paste() {
        if (!navigator.clipboard?.readText) {
            this.pasteManually();

            return;
        }

        let text;

        try {
            text = await navigator.clipboard.readText();
        } catch {
            this.pasteManually();

            return;
        }

        this.value = text.trim();

        if (this.normalize(text) === null) {
            this.error = this.invalidMessage;
            this.$refs.input.focus();

            return;
        }

        this.$nextTick(() => this.$refs.form.requestSubmit());
    },

    pasteManually() {
        this.error = 'Bitte mit Strg+V einfügen.';
        this.$refs.input.focus();
    },

    reset() {
        this.busy = false;
    },
}));

Alpine.start();
