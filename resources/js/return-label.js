/**
 * Form of one DHL return label (PROJ-64): the address as EOCS has it (changeable), "Label erzeugen", download and the
 * mail to the customer. Everything runs on the server; this only sends the confirmed values and shows the answer.
 */
const csrf = () => document.querySelector('meta[name=csrf-token]').content;

const post = async (url, payload) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(payload),
    });

    return { ok: response.ok, status: response.status, data: await response.json().catch(() => ({})) };
};

const firstError = (data, fallback) => Object.values(data.errors || {}).flat()[0] || data.message || fallback;

export default (config) => ({
    ticketNumber: config.ticketNumber ?? '',
    orderNumber: config.orderNumber,
    eocsOrderId: config.eocsOrderId,
    seenArticle: config.seenArticle ?? 0,
    form: { ...config.address },
    addressMissing: config.addressMissing ?? false,
    addressLoading: false,
    addressLoaded: false,
    confirmed: false,
    busy: false,
    error: '',
    detail: '',
    additionalNext: false,
    label: config.label ?? null,
    send: {
        mode: config.ticketNumber ? 'reply' : 'new',
        ticket: config.ticketNumber ?? '',
        email: '',
        text: '',
        signature: config.signature ?? '',
        after: 'reminder',
        days: 7,
        busy: false,
        error: '',
        newer: '',
        resend: '',
        done: config.label?.sent ?? null,
        doneUrl: '',
    },

    init() {
        if (this.label?.instructions) {
            this.send.text = this.label.instructions;
        }
    },

    async loadAddress() {
        if (! config.addressUrl || this.addressLoaded || this.addressLoading) { return }
        this.addressLoading = true;

        try {
            const response = await fetch(config.addressUrl, { headers: { Accept: 'application/json' } });
            const data = await response.json().catch(() => ({}));

            if (! response.ok) { throw new Error(data.message || response.status) }

            if (data.found) { this.form = { ...data.address } }
            this.addressMissing = ! data.found;
            this.addressLoaded = true;
        } catch (exception) {
            this.error = 'Die Adresse konnte nicht aus EOCS geladen werden. Bitte von Hand eintragen.';
            this.addressMissing = true;
        }

        this.addressLoading = false;
    },

    get unclear() {
        return this.label && ['outcome_unknown', 'invalid'].includes(this.label.status);
    },

    get ready() {
        return this.label && this.label.status === 'created' && this.label.download_url;
    },

    async create(additional = false) {
        if (this.busy) { return }
        this.busy = true;
        this.error = '';
        this.detail = '';

        try {
            const { ok, data } = await post(config.storeUrl, {
                ticket_number: this.ticketNumber || null,
                order_number: this.orderNumber,
                eocs_order_id: this.eocsOrderId,
                name: this.form.name,
                street: this.form.street,
                house_number: this.form.house_number,
                postal_code: this.form.postal_code,
                city: this.form.city,
                country: this.form.country,
                confirmed: this.confirmed,
                additional: additional || this.additionalNext,
            });

            if (ok) {
                this.label = data;
                this.send.done = data.sent;
                this.send.text = data.instructions || '';
                this.confirmed = false;
                this.additionalNext = false;

                if (config.attach && data.status === 'created') {
                    this.$dispatch('return-label-ready', { label: data, target: config.target });
                }
            } else {
                this.error = firstError(data, 'Das Label konnte nicht erzeugt werden.');
                this.detail = data.detail || '';
            }
        } catch (exception) {
            this.error = 'Keine Verbindung zur App. Es wurde kein Label erzeugt; bitte erneut versuchen.';
        }

        this.busy = false;
    },

    /**
     * Back to the address for one more label (second parcel, lost label, replacement after an unclear outcome): ordered
     * on purpose, so the label that exists for the same request is not simply returned again.
     */
    newLabel() {
        if (! confirm('Wirklich ein weiteres Label bestellen? Jedes eingelieferte Label wird berechnet.')) { return }
        this.label = null;
        this.send.done = null;
        this.send.resend = '';
        this.confirmed = false;
        this.additionalNext = true;
    },

    async submit(confirmNewer = false, confirmResend = false) {
        if (this.send.busy || ! this.ready) { return }
        this.send.busy = true;
        this.send.error = '';
        if (! confirmNewer) { this.send.newer = '' }
        if (! confirmResend) { this.send.resend = '' }

        try {
            const { ok, data } = await post(config.sendUrl.replace('__LABEL__', this.label.id), {
                mode: this.send.mode,
                ticket_number: this.send.mode === 'reply' ? this.send.ticket : null,
                email: this.send.mode === 'new' ? this.send.email : null,
                text: this.send.text,
                signature: this.send.signature,
                after: this.send.after,
                days: this.send.after === 'reminder' ? this.send.days : null,
                seen_article: this.send.mode === 'reply' && this.send.ticket === (config.ticketNumber ?? '') ? this.seenArticle : 0,
                confirm_newer: confirmNewer,
                confirm_resend: confirmResend,
            });

            if (ok && data.sent) {
                this.send.done = { by: data.by, ticket_number: data.ticket_number, new_ticket: data.new_ticket };
                this.send.doneUrl = data.ticket_url;
            } else if (data.code === 'newer') {
                this.send.newer = data.message;
            } else if (data.code === 'unclear_resend') {
                this.send.resend = data.message;
            } else if (data.code === 'sent') {
                this.send.error = data.message + ' Bitte die Seite neu laden.';
            } else {
                this.send.error = firstError(data, 'Das Senden ist fehlgeschlagen.');
            }
        } catch (exception) {
            this.send.error = 'Keine Verbindung zur App. Es wurde nichts gesendet; bitte erneut versuchen.';
        }

        this.send.busy = false;
    },
});
