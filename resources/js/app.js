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

Alpine.start();
