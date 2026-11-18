/**
 * The lock of the ticket page (PROJ-96). The person holding the lock sends a heartbeat after activity in the
 * visible tab, at most every 30 seconds, and frees the ticket when leaving the page; a hidden tab or an idle
 * person sends nothing, so the lock runs out on the server five minutes after the last activity. A page
 * that shows a ticket locked by someone else asks every 30 seconds whether it is free again.
 */
const INTERVAL = 30_000;
const ACTIVITY = ['pointerdown', 'pointermove', 'keydown', 'wheel', 'scroll', 'touchstart'];

const csrf = () => document.querySelector('meta[name=csrf-token]')?.content ?? '';

export default ({ heartbeat, release, lockedBy }) => ({
    free: false,
    lost: false,
    lostBy: '',
    active: false,
    lastSent: Date.now(),
    timer: null,

    init() {
        const locked = lockedBy !== null;

        if (locked) {
            this.timer = setInterval(() => this.watch(), INTERVAL);

            return;
        }

        const mark = () => {
            if (document.visibilityState === 'visible') {
                this.active = true;
            }
        };
        ACTIVITY.forEach((type) => window.addEventListener(type, mark, { passive: true, capture: true }));
        document.addEventListener('visibilitychange', () => this.beat());
        this.timer = setInterval(() => this.beat(), 5_000);
        // After a reload of the same ticket the old page's release can arrive after this page took the lock: take it again.
        setTimeout(() => this.send(true), 2_000);

        // Leaving the page frees the ticket at once; coming back from the browser's page cache takes it again.
        window.addEventListener('pagehide', () => {
            const data = new FormData();
            data.append('_token', csrf());
            navigator.sendBeacon(release, data);
        });
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                this.send(true);
            }
        });
    },

    destroy() {
        clearInterval(this.timer);
    },

    /** Activity since the last heartbeat in a visible tab: extend the lock, at most every 30 seconds. */
    beat() {
        if (this.active && !this.lost && document.visibilityState === 'visible' && Date.now() - this.lastSent >= INTERVAL) {
            this.send(true);
        }
    },

    /** A locked page asks whether the ticket is free again, only while it is visible. */
    async watch() {
        if (this.free || document.visibilityState !== 'visible') {
            return;
        }

        const state = await this.send(false);

        if (state && state.locked_by === null) {
            this.free = true;
        }
    },

    async send(claim) {
        this.lastSent = Date.now();
        this.active = false;

        try {
            const response = await fetch(heartbeat, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ claim }),
            });

            if (!response.ok) {
                return null;
            }

            const state = await response.json();

            if (claim && state.locked_by !== null) {
                this.lost = true;
                this.lostBy = state.locked_by;
            }

            return state;
        } catch {
            return null;
        }
    },
});
