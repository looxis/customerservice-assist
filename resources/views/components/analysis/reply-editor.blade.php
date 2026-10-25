@props(['result', 'number', 'readonly' => false, 'locked' => false])

@php
    $language = $result['result']['reply']['language'] ?? null;
    $send = $result['send'] ?? ['state' => 'hidden'];
@endphp

{{-- Editable reply draft (PROJ-10): saved in the background while typing,
     copied as plain text, open placeholders counted with the same rule as
     App\Analysis\Pseudonymizer::restore(). Without JavaScript the text stays readable. --}}
<div
    x-data="{
        text: @js($result['reply_text']),
        original: @js($result['reply_original']),
        edited: @js($result['reply_edited']),
        status: '',
        copied: false,
        locked: @js($locked),
        fallback: false,
        timer: null,
        back: @js($result['reply_backtranslation']['text'] ?? ''),
        backFor: @js(($result['reply_backtranslation'] ?? null) && ! $result['reply_backtranslation']['stale'] ? $result['reply_text'] : null),
        backStatus: '',
        async check() {
            this.backStatus = 'loading';
            const checked = this.text;
            try {
                const response = await fetch(@js(route('tickets.analysis.back-translation', ['number' => $number, 'analysis' => $result['id']])), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ text: checked }),
                });
                if (! response.ok) { throw new Error(response.status) }
                this.back = (await response.json()).text;
                this.backFor = checked;
                this.backStatus = '';
            } catch (error) {
                this.backStatus = 'error';
            }
        },
        send: {
            subject: @js($send['subject'] ?? ''),
            signature: @js($send['signature'] ?? ''),
            after: 'close',
            days: 3,
            busy: false,
            error: '',
            newer: '',
            sent: @js($send['state'] === 'sent' ? $send['sent'] : null),
            ticketUrl: @js($send['ticket_url'] ?? null),
            files: [],
            fileError: '',
        },
        sendReady: @js($send['state'] === 'ready'),
        sizeLabel(bytes) { return bytes >= 1048576 ? (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB' },
        get filesSize() { return this.send.files.reduce((sum, file) => sum + file.size, 0) },
        addFiles(list) {
            this.send.fileError = '';
            for (const file of list) {
                if (! ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'].includes(file.type)) { this.send.fileError = `„${file.name}“ hat kein erlaubtes Format. Erlaubt sind PDF, PNG, JPG, JPEG und WebP.`; continue }
                if (this.send.files.length >= 5) { this.send.fileError = 'Es sind höchstens 5 Anhänge möglich.'; break }
                if (this.filesSize + file.size > 5242880) { this.send.fileError = `„${file.name}“ passt nicht mehr dazu: Die Anhänge dürfen zusammen höchstens 5 MB groß sein.`; continue }
                this.send.files.push(file);
            }
        },
        removeFile(index) { this.send.files.splice(index, 1); this.send.fileError = '' },
        paste(event) {
            if (! this.sendReady || this.send.sent) { return }
            const images = [...(event.clipboardData?.files ?? [])].filter((file) => file.type.startsWith('image/'));
            if (images.length === 0 || event.clipboardData.getData('text/plain') !== '') { return }
            event.preventDefault();
            const now = new Date();
            const pad = (value) => String(value).padStart(2, '0');
            const stamp = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}`;
            this.addFiles(images.map((file, index) => new File([file], `Screenshot-${stamp}${images.length > 1 ? '-' + (index + 1) : ''}.${file.type === 'image/jpeg' ? 'jpg' : file.type.split('/')[1]}`, { type: file.type })));
        },
        get message() { return this.text.trim() + (this.send.signature.trim() !== '' ? '\n\n' + this.send.signature.trim() : '') },
        async sendReply(confirmNewer) {
            if (this.send.busy || this.locked || this.text.trim() === '') { return }
            if (this.open > 0 && ! confirm(`Noch ${this.open} offene ${this.open === 1 ? 'Stelle' : 'Stellen'} – trotzdem senden?`)) { return }
            this.send.busy = true;
            this.send.error = '';
            if (! confirmNewer) { this.send.newer = '' }
            try {
                const form = new FormData();
                form.append('subject', this.send.subject);
                form.append('text', this.text);
                form.append('signature', this.send.signature);
                form.append('after', this.send.after);
                if (this.send.after === 'reminder') { form.append('days', this.send.days) }
                form.append('confirm_newer', confirmNewer === true ? '1' : '0');
                this.send.files.forEach((file) => form.append('files[]', file, file.name));
                const response = await fetch(@js(route('tickets.analysis.send', ['number' => $number, 'analysis' => $result['id']])), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: form,
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok && data.sent) {
                    this.send.sent = { by: data.by, at: data.at };
                    this.send.files = [];
                    this.send.ticketUrl = data.ticket_url || this.send.ticketUrl;
                    this.$dispatch('reply-copied');
                } else if (data.code === 'newer') {
                    this.send.newer = data.message;
                } else if (data.code === 'sent') {
                    this.send.error = data.message + ' Bitte die Seite neu laden.';
                } else {
                    this.send.error = response.status === 422 ? (Object.values(data.errors || {}).flat()[0] || 'Bitte Betreff, Text und Anhänge prüfen.') : (data.message || 'Das Senden ist fehlgeschlagen.');
                }
            } catch (error) {
                this.send.error = 'Keine Verbindung zur App. Die Antwort wurde nicht gesendet; bitte erneut versuchen.';
            }
            this.send.busy = false;
        },
        get open() { return (this.text.match(/\[[A-ZÄÖÜ][A-ZÄÖÜ\-]*(?:_\d+)?\]/gu) || []).length },
        changed() {
            this.status = 'saving';
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.save(false), 800);
        },
        async save(original) {
            try {
                const response = await fetch(@js(route('tickets.analysis.reply', ['number' => $number, 'analysis' => $result['id']])), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify(original ? { original: true } : { text: this.text }),
                });
                if (! response.ok) { throw new Error(response.status) }
                this.edited = (await response.json()).edited;
                this.status = 'saved';
            } catch (error) {
                this.status = 'error';
                this.timer = setTimeout(() => this.save(original), 5000);
            }
        },
        restore() {
            if (! confirm('Original der KI wiederherstellen? Deine Änderungen gehen dabei verloren.')) { return }
            clearTimeout(this.timer);
            this.text = this.original;
            this.status = 'saving';
            this.save(true);
        },
        async noteCopy() {
            try {
                await fetch(@js(route('tickets.analysis.copied', ['number' => $number, 'analysis' => $result['id']])), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ text: this.text }),
                });
            } catch (error) {}
        },
        async copy() {
            if (this.locked) { return }
            if (this.open > 0 && ! confirm(`Noch ${this.open} offene ${this.open === 1 ? 'Stelle' : 'Stellen'} – trotzdem kopieren?`)) { return }
            try {
                await navigator.clipboard.writeText(this.text);
                this.fallback = false;
                this.copied = true;
                this.noteCopy();
                this.$dispatch('reply-copied');
                setTimeout(() => (this.copied = false), 2500);
            } catch (error) {
                this.$refs.text.focus();
                this.$refs.text.select();
                this.fallback = true;
                this.noteCopy();
                this.$dispatch('reply-copied');
            }
        },
    }"
    class="mt-6"
    x-on:paste="paste($event)"
>
    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <h3 class="text-sm font-semibold text-slate-900">Antwortentwurf @if ($language)<span class="font-normal text-slate-600">({{ $language }})</span>@endif</h3>
        <p class="text-xs text-slate-600" aria-live="polite">
            <span x-text="edited" @if (! $result['reply_edited']) x-cloak @endif>{{ $result['reply_edited'] }}</span>
            <span x-show="status === 'saving'" x-cloak> · wird gespeichert …</span>
            <span x-show="status === 'saved'" x-cloak class="text-success-700"> · Gespeichert</span>
            <span x-show="status === 'error'" x-cloak class="text-danger-700"> · Nicht gespeichert – wird erneut versucht</span>
        </p>
    </div>

    @if ($result['inserted'] !== [])
        <p class="mt-2 text-xs text-slate-600"><span class="font-semibold">Von der App eingesetzt:</span> {{ implode(', ', $result['inserted']) }} – bitte prüfen.</p>
    @endif
    <p x-show="open > 0" x-cloak class="mt-2 text-xs font-semibold text-warning-700">
        <span x-text="open"></span> <span x-text="open === 1 ? 'Stelle' : 'Stellen'"></span> noch ausfüllen (in eckigen Klammern)
    </p>

    <textarea
        x-ref="text"
        x-model="text"
        @if ($readonly) readonly @else x-on:input="changed()" @endif
        rows="12"
        maxlength="{{ config('analysis.max_reply_length') }}"
        aria-label="Antwortentwurf"
        class="mt-2 field-sizing-content block min-h-48 w-full rounded-md border-0 bg-white px-3 py-2 text-sm leading-relaxed text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand"
    >{{ $result['reply_text'] }}</textarea>

    <div class="mt-3 flex flex-wrap items-center gap-3">
        {{-- With a Zammad token the reply is sent from the app; "Kopieren" stays only where sending is not offered. --}}
        @unless (in_array($send['state'], ['ready', 'sent'], true))
            @if ($locked)
                <x-button x-bind:disabled="true"><x-icon name="doc" size="16" /> Kopieren</x-button>
            @else
                <x-button x-on:click="copy()" x-bind:disabled="text.trim() === ''"><x-icon name="doc" size="16" /> Kopieren</x-button>
            @endif
        @endunless
        @unless ($readonly)
        <x-button variant="secondary" x-show="text !== original" x-cloak x-on:click="restore()"><x-icon name="refresh" size="16" /> Original der KI wiederherstellen</x-button>
        @endunless
        @if ($result['reply_foreign'] ?? false)
            <x-button variant="secondary" x-on:click="check()" x-bind:disabled="backStatus === 'loading' || text.trim() === ''"><x-icon name="text" size="16" /> Auf Deutsch gegenlesen</x-button>
        @endif
        @if ($locked)<span role="status" class="text-sm font-medium text-danger-700">Erst kopierbar, wenn die Regelprüfung bestätigt ist (siehe oben).</span>@endif
        <span x-show="copied" x-cloak role="status" class="text-sm font-medium text-success-700">Kopiert</span>
        <span x-show="fallback" x-cloak role="status" class="text-sm text-slate-600">Der Browser erlaubt das Kopieren hier nicht. Der Text ist markiert – bitte mit Strg+C kopieren.</span>
    </div>

    @if ($send['state'] === 'no_token')
        <p class="mt-3 rounded-md bg-slate-50 p-3 text-sm text-slate-700 ring-1 ring-slate-200 ring-inset">Bitte Token hinterlegen, dann ist ein Direktversand möglich. Bis dahin: „Kopieren“ und in Zammad senden.</p>
    @elseif ($send['state'] === 'blocked')
        <p class="mt-3 rounded-md bg-slate-50 p-3 text-sm text-slate-700 ring-1 ring-slate-200 ring-inset">{{ $send['reason'] }}</p>
    @elseif (in_array($send['state'], ['ready', 'sent'], true))
        {{-- Send the reply from the app (PROJ-19): recipient from the ticket, subject and signature changeable, only on click. --}}
        <section class="mt-5 rounded-lg border border-slate-200 bg-slate-50/60 p-4" aria-label="Antwort versenden" id="antwort-versenden">
            <div x-show="send.sent" @if ($send['state'] !== 'sent') x-cloak @endif role="status" class="text-sm text-success-700">
                <p class="font-semibold">Gesendet von <span x-text="send.sent ? send.sent.by : ''">{{ $send['sent']['by'] ?? '' }}</span> am <span x-text="send.sent ? send.sent.at : ''">{{ $send['sent']['at'] ?? '' }}</span> Uhr</p>
                <p class="mt-1" x-show="send.ticketUrl"><a x-bind:href="send.ticketUrl" target="_blank" rel="noopener" class="font-medium underline">Ticket in Zammad öffnen</a></p>
            </div>
            @if ($send['state'] === 'ready')
                <div x-show="! send.sent" class="space-y-3">
                    <h3 class="text-sm font-semibold text-slate-900">Antwort versenden</h3>
                    <p class="text-sm text-slate-700"><span class="font-medium">An:</span> {{ $send['to'] }}</p>
                    <div>
                        <label for="send-subject" class="block text-sm font-medium text-slate-900">Betreff</label>
                        <input id="send-subject" type="text" x-model="send.subject" maxlength="300" autocomplete="off" class="mt-1 block w-full rounded-md border-0 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand">
                    </div>
                    <div>
                        <label for="send-signature" class="block text-sm font-medium text-slate-900">Signatur <span class="font-normal text-slate-600">(wird unter den Antworttext gesetzt)</span></label>
                        <textarea id="send-signature" x-model="send.signature" rows="4" maxlength="600" class="mt-1 field-sizing-content block w-full rounded-md border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand"></textarea>
                    </div>
                    <div>
                        <p class="block text-sm font-medium text-slate-900">Anhänge <span class="font-normal text-slate-600">(PDF, PNG, JPG, WebP · bis zu 5 Dateien, zusammen höchstens 5 MB)</span></p>
                        <ul x-show="send.files.length > 0" x-cloak class="mt-1 space-y-1 text-sm" aria-label="Anhänge der Antwort">
                            <template x-for="(file, index) in send.files" :key="file.name + index">
                                <li class="flex flex-wrap items-center gap-x-3 text-slate-900">
                                    <span class="font-mono" x-text="file.name"></span>
                                    <span class="text-slate-600" x-text="sizeLabel(file.size)"></span>
                                    <button type="button" x-on:click="removeFile(index)" class="font-semibold text-slate-600 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-brand" x-bind:aria-label="'Anhang ' + file.name + ' entfernen'">Entfernen</button>
                                </li>
                            </template>
                        </ul>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <input type="file" x-ref="files" multiple accept=".pdf,.png,.jpg,.jpeg,.webp,application/pdf,image/png,image/jpeg,image/webp" class="sr-only" tabindex="-1" aria-hidden="true" x-on:change="addFiles($event.target.files); $event.target.value = ''">
                            <x-button variant="secondary" x-on:click="$refs.files.click()"><x-icon name="upload" size="16" /> Datei anhängen</x-button>
                            <span class="text-xs text-slate-600">Einen Screenshot aus der Zwischenablage fügst du mit Strg+V ein.</span>
                        </div>
                        <p x-show="send.fileError !== ''" x-cloak role="alert" class="mt-1 text-sm font-medium text-danger-700" x-text="send.fileError"></p>
                    </div>
                    <details class="text-sm">
                        <summary class="cursor-pointer font-medium text-slate-700">Vorschau der Nachricht</summary>
                        <p class="mt-2 whitespace-pre-line rounded-md bg-white p-3 text-slate-900 ring-1 ring-slate-200 ring-inset" x-text="message"></p>
                        <p x-show="send.files.length > 0" x-cloak class="mt-2 text-slate-900"><span class="font-medium">Anhänge:</span> <span x-text="send.files.map((file) => file.name).join(', ')"></span></p>
                    </details>
                    <fieldset>
                        <legend class="text-sm font-medium text-slate-900">Danach</legend>
                        <div class="mt-1 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-slate-900">
                            <label class="inline-flex items-center gap-2"><input type="radio" value="close" x-model="send.after" class="text-brand focus:ring-brand"> Ticket schließen</label>
                            <label class="inline-flex items-center gap-2"><input type="radio" value="reminder" x-model="send.after" class="text-brand focus:ring-brand"> Erinnerung in
                                <select x-model.number="send.days" x-bind:disabled="send.after !== 'reminder'" aria-label="Tage bis zur Erinnerung" class="rounded-md border-0 py-1 pl-2 pr-7 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand"><option value="1">1</option><option value="3">3</option><option value="7">7</option></select> Tagen</label>
                        </div>
                    </fieldset>
                    <div x-show="send.newer !== ''" x-cloak role="alert" class="rounded-md bg-warning-500/10 p-3 text-sm text-slate-900 ring-1 ring-warning-500/30 ring-inset">
                        <p class="font-semibold text-warning-700" x-text="send.newer"></p>
                        <div class="mt-2 flex flex-wrap gap-3">
                            @if ($send['ticket_url'] ?? null)<a href="{{ $send['ticket_url'] }}" target="_blank" rel="noopener" class="text-sm font-medium underline">Ticket in Zammad öffnen</a>@endif
                            <x-button variant="secondary" x-on:click="sendReply(true)" x-bind:disabled="send.busy">Trotzdem senden</x-button>
                        </div>
                    </div>
                    <p x-show="send.error !== ''" x-cloak role="alert" class="text-sm font-medium text-danger-700" x-text="send.error"></p>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-button x-on:click="sendReply(false)" x-bind:disabled="send.busy || text.trim() === '' || send.subject.trim() === ''"><x-icon name="chat" size="16" /> <span x-text="send.busy ? 'Wird gesendet …' : 'Antwort versenden'">Antwort versenden</span></x-button>
                    </div>
                    <p class="text-xs text-slate-600">Die Antwort wird über Zammad versendet und erscheint dort im Ticketverlauf. Gesendet wird der Antwortentwurf oben (änderbar) mit der Signatur. Anhänge werden mitgesendet und nicht in der App gespeichert.</p>
                </div>
            @endif
        </section>
    @endif

    @if ($result['reply_foreign'] ?? false)
        {{-- German back translation for checking (PROJ-28); the original is what gets sent. --}}
        <p x-show="backStatus === 'loading'" x-cloak class="mt-3 text-sm text-slate-600" role="status">Der Entwurf wird übersetzt …</p>
        <p x-show="backStatus === 'error'" x-cloak class="mt-3 text-sm text-danger-700" role="alert">Die Übersetzung ist gerade nicht möglich. Bitte erneut versuchen.</p>
        <div x-show="back !== ''" @if (($result['reply_backtranslation']['text'] ?? '') === '') x-cloak @endif class="mt-3 rounded-md bg-trust-500/10 p-3 text-sm ring-1 ring-trust-500/20 ring-inset">
            <p class="text-xs font-semibold text-trust-500">Rückübersetzung zur Kontrolle – verschickt wird das Original · KI-Übersetzung</p>
            <p x-show="backFor !== text" @if (! ($result['reply_backtranslation']['stale'] ?? false)) x-cloak @endif class="mt-1 text-xs font-semibold text-warning-700">Entwurf seither geändert – erneut gegenlesen.</p>
            <p class="mt-2 whitespace-pre-line text-slate-900" x-text="back">{{ $result['reply_backtranslation']['text'] ?? '' }}</p>
        </div>
    @endif
</div>
