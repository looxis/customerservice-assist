@php
    $icons = ['plus', 'grid', 'grid2', 'folder', 'template', 'layers', 'doc', 'file', 'settings', 'chevron-right', 'chevron-left', 'chevron-down', 'search', 'bell', 'help', 'warning', 'sparkle', 'upload', 'download', 'refresh', 'edit', 'check', 'x', 'image', 'text', 'trash', 'pin'];
@endphp

<x-layouts.app title="Komponenten-Übersicht" width="5xl">
    <x-alert>Diese Seite ist nur in der lokalen Entwicklung erreichbar. Grundlage: <span class="font-mono">docs/design-system.md</span>.</x-alert>

    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Farben</h2>
        <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">Brand</p>
        <div class="mt-2 grid grid-cols-5 gap-2 sm:grid-cols-10">
            <div class="h-10 rounded-md bg-brand-50 ring-1 ring-inset ring-slate-200" title="brand-50"></div>
            <div class="h-10 rounded-md bg-brand-100" title="brand-100"></div>
            <div class="h-10 rounded-md bg-brand-200" title="brand-200"></div>
            <div class="h-10 rounded-md bg-brand-300" title="brand-300"></div>
            <div class="h-10 rounded-md bg-brand-400" title="brand-400"></div>
            <div class="h-10 rounded-md bg-brand-500" title="brand-500"></div>
            <div class="h-10 rounded-md bg-brand-600" title="brand-600"></div>
            <div class="h-10 rounded-md bg-brand-700" title="brand-700"></div>
            <div class="h-10 rounded-md bg-brand-800" title="brand-800"></div>
            <div class="h-10 rounded-md bg-brand-900" title="brand-900"></div>
        </div>
        <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">Slate</p>
        <div class="mt-2 grid grid-cols-5 gap-2 sm:grid-cols-10">
            <div class="h-10 rounded-md bg-slate-50 ring-1 ring-inset ring-slate-200" title="slate-50"></div>
            <div class="h-10 rounded-md bg-slate-100" title="slate-100"></div>
            <div class="h-10 rounded-md bg-slate-200" title="slate-200"></div>
            <div class="h-10 rounded-md bg-slate-300" title="slate-300"></div>
            <div class="h-10 rounded-md bg-slate-400" title="slate-400"></div>
            <div class="h-10 rounded-md bg-slate-500" title="slate-500"></div>
            <div class="h-10 rounded-md bg-slate-600" title="slate-600"></div>
            <div class="h-10 rounded-md bg-slate-700" title="slate-700"></div>
            <div class="h-10 rounded-md bg-slate-800" title="slate-800"></div>
            <div class="h-10 rounded-md bg-slate-900" title="slate-900"></div>
        </div>
        <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-400">Status</p>
        <div class="mt-2 grid grid-cols-5 gap-2 sm:grid-cols-10">
            <div class="h-10 rounded-md bg-success-500" title="success-500"></div>
            <div class="h-10 rounded-md bg-warning-500" title="warning-500"></div>
            <div class="h-10 rounded-md bg-danger-500" title="danger-500"></div>
            <div class="h-10 rounded-md bg-trust-500" title="trust-500"></div>
            <div class="h-10 rounded-md bg-ink-900" title="ink-900"></div>
        </div>
    </x-card>

    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Typografie</h2>
        <div class="mt-4 space-y-3">
            <p class="font-display text-lg font-semibold text-slate-900">Seitentitel – Bricolage Grotesque, 18 px</p>
            <p class="text-base font-semibold text-slate-900">Karten- und Abschnittsüberschrift, 16 px</p>
            <p class="text-sm font-semibold text-slate-900">Unterüberschrift, betonter Wert, 14 px</p>
            <p class="text-sm text-slate-900">Fließtext – Manrope, 14 px. Franz jagt im komplett verwahrlosten Taxi quer durch Bayern.</p>
            <p class="text-sm text-slate-600">Sekundärtext und Feld-Hinweis in slate-600.</p>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Abschnitts-Label</p>
            <p class="font-mono text-sm text-slate-900">JetBrains Mono – POLICY-003 · #4711 · 97af4ee</p>
            <p><a href="#" class="text-sm font-medium text-brand hover:text-brand-hover">Primärer Link</a></p>
        </div>
    </x-card>

    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Karte</h2>
        <p class="mt-1 text-sm text-slate-600">Standard-Container für Seiteninhalte. Langer Inhalt ohne Leerzeichen bricht um:</p>
        <p class="mt-2 font-mono text-sm text-slate-900">https://example.com/ein/sehr/langer/pfad/ohne/leerzeichen/der/nicht/horizontal/scrollen/darf/sondern/innerhalb/der/karte/umbrechen/muss</p>
    </x-card>

    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Buttons</h2>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <x-button>Primary</x-button>
            <x-button variant="secondary">Secondary</x-button>
            <x-button variant="danger">Danger</x-button>
            <x-button><x-icon name="sparkle" /> Mit Icon</x-button>
            <x-button href="#">Als Link</x-button>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <x-button disabled>Primary deaktiviert</x-button>
            <x-button variant="secondary" disabled>Secondary deaktiviert</x-button>
            <x-button variant="danger" disabled>Danger deaktiviert</x-button>
        </div>
    </x-card>

    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Formularfelder</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-2">
            <x-input name="sg_plain" label="Input" placeholder="Platzhalter" />
            <x-input name="sg_hint" label="Mit Hinweis" hint="Die Ticketnummer steht in Zammad oben links." />
            <x-input name="sg_required" label="Pflichtfeld" required />
            <x-input name="sg_error" label="Mit Fehler" value="abc" hint="Dieser Hinweis ist bei Fehler ausgeblendet." error="Bitte gib eine gültige Ticketnummer ein." />
            <x-select name="sg_select" label="Select" hint="Auswahl aus einer festen Liste.">
                <option value="">Bitte wählen …</option>
                <option>Erste Option</option>
                <option>Zweite Option</option>
            </x-select>
            <x-select name="sg_select_error" label="Select mit Fehler" error="Bitte triff eine Auswahl.">
                <option value="">Bitte wählen …</option>
            </x-select>
            <x-textarea name="sg_textarea" label="Textarea" hint="Standard: drei Zeilen." placeholder="Zusätzliche Informationen / eigene Einschätzung" />
            <x-textarea name="sg_textarea_error" label="Textarea mit Fehler" error="Der Text ist zu lang." />
            <x-input name="sg_disabled" label="Deaktiviert" value="Nicht änderbar" disabled />
        </div>
    </x-card>

    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Badges</h2>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <x-badge>Neutral</x-badge>
            <x-badge tone="success">Erfolg</x-badge>
            <x-badge tone="info">Info</x-badge>
            <x-badge tone="warning">Warnung</x-badge>
            <x-badge tone="danger">Fehler</x-badge>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <x-badge compact>Kompakt</x-badge>
            <x-badge tone="success" compact>Kompakt</x-badge>
            <x-badge tone="info" compact>Kompakt</x-badge>
            <x-badge tone="warning" compact>Kompakt</x-badge>
            <x-badge tone="danger" compact>Kompakt</x-badge>
        </div>
    </x-card>

    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Alerts</h2>
        <div class="mt-4 space-y-3">
            <x-alert>Info: Für dieses Ticket gibt es bereits eine frühere Analyse.</x-alert>
            <x-alert type="success">Erfolg: Der Antwortentwurf wurde kopiert.</x-alert>
            <x-alert type="warning">Warnung: Die Bestellung konnte nicht automatisch erkannt werden.</x-alert>
            <x-alert type="error">Fehler: Zammad ist gerade nicht erreichbar. Bitte versuche es in einem Moment noch einmal.</x-alert>
        </div>
    </x-card>

    <x-card>
        <h2 class="text-base font-semibold text-slate-900">Icons</h2>
        <div class="mt-4 grid grid-cols-3 gap-4 sm:grid-cols-5 md:grid-cols-7">
            @foreach ($icons as $icon)
                <div class="flex flex-col items-center gap-2 text-slate-700">
                    <x-icon :name="$icon" />
                    <span class="font-mono text-xs text-slate-600">{{ $icon }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-6 flex flex-wrap items-center gap-4">
            <x-icon name="sparkle" size="28" class="text-brand" />
            <x-icon name="trash" size="20" stroke="2" class="text-danger-500" />
            <x-icon name="check" class="text-success-500" />
            <span class="text-sm text-slate-600">Größe, Strichstärke und Farbe sind anpassbar. Unbekannter Name:</span>
            <x-icon name="gibt-es-nicht" />
        </div>
    </x-card>

    <x-card x-data>
        <h2 class="text-base font-semibold text-slate-900">Lade-Overlay</h2>
        <p class="mt-1 text-sm text-slate-600">Lässt sich nicht wegklicken. In dieser Vorschau schließt es sich nach drei Sekunden von selbst.</p>
        <div class="mt-4">
            <x-button
                variant="secondary"
                x-on:click="$dispatch('loading-start', { title: 'Ticket wird analysiert …', text: 'Das dauert in der Regel wenige Sekunden.' }); setTimeout(() => $dispatch('loading-stop'), 3000)"
            >
                <x-icon name="refresh" /> Overlay anzeigen
            </x-button>
        </div>
    </x-card>
</x-layouts.app>
