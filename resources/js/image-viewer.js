/**
 * Viewer for image attachments of a ticket (PROJ-60): the image is fetched once (the server keeps it for 30 days),
 * fitted into the window and can be zoomed (mouse wheel, + and -) and moved (dragging, arrow keys).
 */
export default () => ({
    open: false,
    name: '',
    url: '',
    downloadUrl: '#',
    opener: null,
    src: '',
    loading: false,
    error: '',
    scale: 1,
    fitScale: 1,
    x: 0,
    y: 0,
    dragging: false,
    last: null,
    moved: false,

    show(detail) {
        this.name = detail.name;
        this.url = detail.url;
        this.downloadUrl = detail.downloadUrl;
        this.opener = detail.opener ?? null;
        this.open = true;
        document.body.classList.add('overflow-hidden');
        this.$nextTick(() => this.$refs.close.focus());
        this.load();
    },

    async load() {
        this.release();
        this.error = '';
        this.loading = true;

        try {
            const response = await fetch(this.url, { headers: { Accept: 'image/*' } });

            if (!response.ok) {
                let message = 'Der Anhang konnte nicht geladen werden. Bitte erneut versuchen.';

                try {
                    message = (await response.json()).message ?? message;
                } catch {
                    // keep the general message
                }

                throw new Error(message);
            }

            this.src = URL.createObjectURL(await response.blob());
        } catch (exception) {
            this.error = exception.message || 'Der Anhang konnte nicht geladen werden. Bitte erneut versuchen.';
            this.loading = false;
        }
    },

    loaded() {
        this.loading = false;
        this.fit();
    },

    fit() {
        const image = this.$refs.image;
        const stage = this.$refs.stage;

        if (!image || !image.naturalWidth) {
            return;
        }

        this.fitScale = Math.min(stage.clientWidth / image.naturalWidth, stage.clientHeight / image.naturalHeight, 3);
        this.scale = this.fitScale;
        this.x = (stage.clientWidth - image.naturalWidth * this.scale) / 2;
        this.y = (stage.clientHeight - image.naturalHeight * this.scale) / 2;
    },

    zoom(factor, centerX = null, centerY = null) {
        const stage = this.$refs.stage;
        const next = Math.min(Math.max(this.scale * factor, this.fitScale / 2, 0.05), 16);
        const cx = centerX ?? stage.clientWidth / 2;
        const cy = centerY ?? stage.clientHeight / 2;
        const ratio = next / this.scale;

        this.x = cx - (cx - this.x) * ratio;
        this.y = cy - (cy - this.y) * ratio;
        this.scale = next;
    },

    wheel(event) {
        const box = this.$refs.stage.getBoundingClientRect();

        this.zoom(event.deltaY < 0 ? 1.15 : 1 / 1.15, event.clientX - box.left, event.clientY - box.top);
    },

    dragStart(event) {
        if (!this.src || event.button !== 0) {
            return;
        }

        this.dragging = true;
        this.moved = false;
        this.last = { x: event.clientX, y: event.clientY };
        this.$refs.stage.setPointerCapture(event.pointerId);
    },

    dragMove(event) {
        if (!this.dragging) {
            return;
        }

        if (Math.abs(event.clientX - this.last.x) + Math.abs(event.clientY - this.last.y) > 0) {
            this.moved = true;
        }

        this.x += event.clientX - this.last.x;
        this.y += event.clientY - this.last.y;
        this.last = { x: event.clientX, y: event.clientY };
    },

    backdrop() {
        if (!this.moved) {
            this.close();
        }

        this.moved = false;
    },

    dragEnd() {
        this.dragging = false;
    },

    key(event) {
        if (!this.open) {
            return;
        }

        const step = 60;
        const handled = {
            Escape: () => this.close(),
            '+': () => this.zoom(1.25),
            '=': () => this.zoom(1.25),
            '-': () => this.zoom(1 / 1.25),
            '0': () => this.fit(),
            ArrowLeft: () => (this.x += step),
            ArrowRight: () => (this.x -= step),
            ArrowUp: () => (this.y += step),
            ArrowDown: () => (this.y -= step),
        }[event.key];

        if (event.key === 'Tab') {
            const controls = [...this.$root.querySelectorAll('button, a[href]')].filter((element) => element.offsetParent !== null);

            if (controls.length > 0) {
                const first = controls[0];
                const lastControl = controls[controls.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    lastControl.focus();
                } else if (!event.shiftKey && document.activeElement === lastControl) {
                    event.preventDefault();
                    first.focus();
                }
            }

            return;
        }

        if (handled) {
            event.preventDefault();
            handled();
        }
    },

    close() {
        this.open = false;
        document.body.classList.remove('overflow-hidden');
        this.release();
        this.opener?.focus?.();
    },

    release() {
        if (this.src) {
            URL.revokeObjectURL(this.src);
        }

        this.src = '';
    },
});
