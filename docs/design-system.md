# LOOXIS Design System

Design-Tokens, Typografie, Icon-Set und Komponenten-Patterns aus **LOOXIS Content Studio** (Repo `looxis-productforge`), aufbereitet zur Übernahme in weitere Apps. Dieses Dokument ist eigenständig: Alle Werte, die Tailwind-Konfiguration und die vollständigen SVG-Pfaddaten der Icons sind enthalten, es wird kein Zugriff auf das Quell-Repo benötigt.

**Referenz-Stack der Quelle:** Laravel + Blade, Tailwind CSS v3, Alpine.js. Die Tokens selbst sind stack-unabhängig (siehe [CSS-Variablen](#css-variablen-ohne-tailwind) für Projekte ohne Tailwind).

## Inhalt

1. [Grundprinzipien](#grundprinzipien)
2. [Farben](#farben)
3. [Typografie](#typografie)
4. [Radius, Schatten, Abstände](#radius-schatten-abstände)
5. [Icons](#icons)
6. [Komponenten](#komponenten)
7. [Layout-Shell](#layout-shell)
8. [Globale Basis-Styles](#globale-basis-styles)
9. [Setup in einer neuen App](#setup-in-einer-neuen-app)

---

## Grundprinzipien

- **Helle Oberfläche, ein Akzent.** Weiße Karten auf hellgrauem Seitenhintergrund (`slate-100`), Orange (`brand`) als einzige Akzentfarbe für Primäraktionen, Links, aktive Navigation und Fokus.
- **Tokens statt Hex-Werte.** Im Markup stehen nur Token-Klassen (`bg-brand`, `text-slate-500`), nie rohe Farbwerte.
- **Ringe statt Rahmen.** Karten, Felder und Badges werden mit `ring-1` (innen: `ring-inset`) konturiert, nicht mit `border`. Rahmen (`border-slate-200`) nur für Trennlinien der Layout-Shell.
- **Statusfarben getönt.** Status-Flächen nutzen die Statusfarbe mit 10 % Deckkraft als Hintergrund, 20 % als Ring und 100 % als Text — keine vollflächigen Statusfarben außer beim Danger-Button.
- **Kein Dark Mode.** Das System ist ausschließlich hell definiert.

---

## Farben

### Brand (Orange)

| Token | Hex | Verwendung |
|---|---|---|
| `brand-50` / `brand-tint` | `#fff5ef` | Helle Akzentfläche (aktive Sidebar-Zeile) |
| `brand-100` | `#ffe6d5` | |
| `brand-200` | `#ffc9a8` | Textauswahl (`::selection`) |
| `brand-300` | `#ffa979` | |
| `brand-400` | `#ff8b51` | |
| **`brand` / `brand-500`** | **`#fe7437`** | **Primärfarbe: Buttons, Links, Fokus-Ring, Toggle aktiv** |
| `brand-600` / `brand-hover` | `#e8612a` | Hover-Zustand |
| `brand-700` / `brand-press` | `#cf5421` | Pressed-Zustand; Text auf `brand-tint` (aktive Navigation) |
| `brand-800` | `#a4421a` | |
| `brand-900` | `#7a3214` | |

### Neutral (Slate)

Ersetzt Tailwinds Standard-`gray`/`slate` vollständig.

| Token | Hex | Verwendung |
|---|---|---|
| `slate-50` | `#f8f9fb` | Hover-Fläche (Sekundär-Button, Nav-Zeile) |
| `slate-100` | `#f3f4f6` | Seitenhintergrund, neutrale Badge-Fläche, Tabellen-Trennlinien |
| `slate-200` | `#e3e6ea` | Karten-Ring, Trennlinien, Toggle inaktiv |
| `slate-300` | `#c4c9d1` | Feld-Ring, Scrollbar |
| `slate-400` | `#9098a3` | Placeholder, Abschnitts-Labels, dezente Icons |
| `slate-500` | `#8a8f9a` | Sekundärtext, Hinweise, Tabellenkopf |
| `slate-600` | `#6a7080` | Sekundäre Links, neutraler Badge-Text |
| `slate-700` | `#4a4f59` | Navigationstext (inaktiv) |
| `slate-800` | `#2f343d` | |
| `slate-900` | `#1d2026` | Standard-Textfarbe, Überschriften |

### Ink (sehr dunkel)

| Token | Hex | Verwendung |
|---|---|---|
| `ink-800` | `#1d2026` | |
| `ink-900` | `#15191f` | Modal-/Lade-Overlay (`bg-ink-900/50`), Text in Textauswahl |

### Statusfarben

| Token | Hex | Bedeutung |
|---|---|---|
| `success-500` | `#2ea562` | Erfolg, „Aktiv", abgeschlossen |
| `warning-500` | `#f6a623` | Warnung, offene Aufgabe, hohe Priorität |
| `danger-500` | `#e0394f` | Fehler, Löschen, Pflichtfeld-Stern |
| `trust-500` | `#1b4ebd` | Info (Standard-Alert), „in Arbeit" |

Getöntes Standard-Muster für jede Statusfarbe:
`bg-{farbe}-500/10 text-{farbe}-500 ring-1 ring-inset ring-{farbe}-500/20`

### Weitere Akzente (definiert, aktuell ungenutzt)

| Token | Hex |
|---|---|
| `sun-500` | `#ff9900` |
| `cream-100` | `#faf7f2` |

### Mapping von Tailwind-Defaults

Für Code, der noch Standard-Tailwind-Farben verwendet:
`indigo-600 → brand`, `indigo-500 → brand-hover`, `indigo-700 → brand-press`, `indigo-50 → brand-tint`, `gray-{n} → slate-{n}`, `red-500/600 → danger-500`, `green-600/700 → success-500`, `blue-* → trust-500`.

---

## Typografie

### Schriften

| Token | Schrift | Fallback | Einsatz |
|---|---|---|---|
| `font-display` | **Bricolage Grotesque** | Manrope, system-ui, sans-serif | Überschriften `h1`–`h4`, App-Name |
| `font-body` | **Manrope** | system-ui, sans-serif | Fließtext, UI, Formulare (Standard auf `body`) |
| `font-mono` | **JetBrains Mono** | monospace | Code, technische Werte (SKU, IDs) |

Alle drei sind Google Fonts. Geladene Schnitte: Bricolage Grotesque 400–800 (optische Größe 12–96), Manrope 400–800, JetBrains Mono 400/500/700.

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
```

Überschriften erhalten global `tracking-tight`; Text wird mit `antialiased` gerendert.

### Textstile

Die Skala ist bewusst klein — die App arbeitet fast ausschließlich mit 12, 14, 16 und 18 px.

| Rolle | Klassen | Größe |
|---|---|---|
| Seitentitel (Topbar), App-Name | `font-display text-lg font-semibold text-slate-900` | 18 px |
| Karten-/Abschnittsüberschrift | `text-base font-semibold text-slate-900` | 16 px |
| Unterüberschrift, betonter Wert | `text-sm font-semibold text-slate-900` | 14 px |
| Feld-Label | `text-sm font-medium text-slate-900` | 14 px |
| Fließtext | `text-sm text-slate-900` | 14 px |
| Sekundärtext, Feld-Hinweis | `text-sm text-slate-500` | 14 px |
| Fehlermeldung am Feld | `text-sm text-danger-500` | 14 px |
| Abschnitts-Label (Overline) | `text-xs font-medium uppercase tracking-wide text-slate-400` | 12 px |
| Tabellenkopf | `text-xs font-semibold text-slate-500` | 12 px |
| Badge | `text-xs font-medium` | 12 px |
| Primärer Link | `text-sm font-medium text-brand hover:text-brand-hover` | 14 px |
| Sekundärer Link | `text-sm font-semibold text-slate-600 hover:text-slate-900` | 14 px |
| Destruktiver Link | `text-sm font-medium text-slate-400 hover:text-danger-500` | 14 px |

Schriftgewichte: `font-medium` (500) für Labels und Links, `font-semibold` (600) für Überschriften und Buttons. `font-bold` wird in der UI nicht verwendet.

---

## Radius, Schatten, Abstände

### Radius

| Token | Wert | Verwendung |
|---|---|---|
| `rounded-sm` | 4 px | Kleine Elemente |
| `rounded-md` | 8 px | Buttons, Formularfelder, Alerts, Nav-Zeilen |
| `rounded-lg` | 12 px | Karten, Modals |
| `rounded-xl` | 16 px | Große Flächen |
| `rounded-pill` | 9999 px | Badges, Toggle |

### Schatten

| Token | Wert | Verwendung |
|---|---|---|
| `shadow-1` | `0 1px 2px rgba(15,17,21,.05), 0 1px 1px rgba(15,17,21,.04)` | Karten, Buttons, Formularfelder |
| `shadow-2` | `0 10px 24px -8px rgba(15,17,21,.14)` | Hover/angehobene Elemente |
| `shadow-3` | `0 24px 48px -12px rgba(15,17,21,.25)` | Modals, Overlays |

### Abstände

Standard-Tailwind-Skala (4-px-Raster). Wiederkehrende Werte:

- Karten-Innenabstand `p-6`, Modal `p-6`, Lade-Overlay `p-8`
- Vertikaler Abstand zwischen Karten/Seitenblöcken `space-y-6`
- Button `px-3 py-2`, Formularfeld `px-3 py-1.5`, Badge `px-2.5 py-0.5`
- Abstand Label → Feld und Feld → Hinweis `mt-1`
- Tabellenzellen `px-6 py-4`, Tabellenkopf `px-6 py-3`
- Abstand Icon → Text `gap-2` (Buttons) bzw. `gap-3` (Navigation)

---

## Icons

### Stil

Eigenes, handgezeichnetes **Stroke-Icon-Set** (27 Icons) — keine Icon-Library, kein Icon-Font. Optisch kompatibel mit Feather/Lucide, falls ein Icon fehlt und ergänzt werden muss.

| Eigenschaft | Wert |
|---|---|
| ViewBox | `0 0 24 24` |
| Füllung | `none` |
| Kontur | `currentColor` (Farbe wird über die Textfarbe gesteuert) |
| Strichstärke | `1.8` (Standard), `2` für kleine/betonte Icons |
| Linienenden / -ecken | `round` / `round` |
| Standardgröße | 18 px; 28 px für den Lade-Spinner |

### SVG-Rahmen

```html
<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
  <!-- Pfaddaten des Icons -->
</svg>
```

### Icon-Set

| Name | Bedeutung / Einsatz | Pfaddaten |
|---|---|---|
| `plus` | Hinzufügen, Neu | `<path d="M12 5v14M5 12h14"/>` |
| `grid` | Übersicht, Dashboard | `<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>` |
| `grid2` | Raster, Aufteilung | `<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 12h18M12 3v18"/>` |
| `folder` | Produkte, Ordner | `<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>` |
| `template` | Vorlagen, Templates | `<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>` |
| `layers` | Ebenen, Sammlungen, Projekte | `<path d="m12 2 9 5-9 5-9-5 9-5zM3 12l9 5 9-5M3 17l9 5 9-5"/>` |
| `doc` | Dokument mit Text | `<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/><path d="M9 9h1M9 13h6M9 17h6"/>` |
| `file` | Datei (leer) | `<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/>` |
| `settings` | Einstellungen | `<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.2.61.76 1.05 1.51 1.05H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>` |
| `chevron-right` | Weiter, Aufklappen | `<path d="m9 18 6-6-6-6"/>` |
| `chevron-left` | Zurück | `<path d="m15 18-6-6 6-6"/>` |
| `chevron-down` | Dropdown, Aufklappen | `<path d="m6 9 6 6 6-6"/>` |
| `search` | Suche | `<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>` |
| `bell` | Benachrichtigungen | `<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>` |
| `help` | Hilfe, Info | `<circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>` |
| `warning` | Warnung | `<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>` |
| `sparkle` | KI-Funktionen, Generieren | `<path d="m12 3 2.5 5.5L20 11l-5.5 2.5L12 19l-2.5-5.5L4 11l5.5-2.5z"/>` |
| `upload` | Hochladen | `<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>` |
| `download` | Herunterladen, Import | `<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>` |
| `refresh` | Neu laden, Lade-Spinner (mit `animate-spin`) | `<path d="M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5"/>` |
| `edit` | Bearbeiten | `<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>` |
| `check` | Bestätigt, Erledigt | `<path d="M20 6 9 17l-5-5"/>` |
| `x` | Schließen, Entfernen | `<path d="M18 6 6 18M6 6l12 12"/>` |
| `image` | Bild, Fotos | `<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/>` |
| `text` | Text, Keywords | `<path d="M4 6h16M4 12h16M4 18h10"/>` |
| `trash` | Löschen | `<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>` |
| `pin` | Anheften, Fixieren | `<path d="M9 4h6l-1 6 3 3v2H7v-2l3-3z"/><path d="M12 15v5"/>` |

### Blade-Komponente

In der Quelle liegen die Pfaddaten in einer einzigen anonymen Blade-Komponente (`resources/views/components/icon.blade.php`): ein `match ($name)` liefert die Pfaddaten, die in den oben gezeigten SVG-Rahmen eingesetzt werden. Props: `name`, `size` (Standard 18), `stroke` (Standard 1.8); weitere Attribute (z. B. `class`) werden an das `<svg>` durchgereicht.

```blade
<x-icon name="folder" />
<x-icon name="trash" size="20" stroke="2" />
<x-icon name="refresh" size="28" class="animate-spin text-brand" />
```

---

## Komponenten

Klassenangaben sind Tailwind-Utilities auf Basis der obigen Tokens.

### Karte

Standard-Container für Seiteninhalte.

```html
<div class="rounded-lg bg-white p-6 shadow-1 ring-1 ring-slate-200">…</div>
```

### Button

Basis: `inline-flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-semibold shadow-1 transition disabled:cursor-not-allowed disabled:opacity-50`

| Variante | Zusätzliche Klassen | Einsatz |
|---|---|---|
| `primary` | `bg-brand text-white hover:bg-brand-hover` | Hauptaktion, eine pro Ansicht |
| `secondary` | `bg-white text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50` | Nebenaktionen, Abbrechen |
| `danger` | `bg-danger-500 text-white hover:bg-danger-500/90` | Löschen, destruktive Aktionen |

### Formularfelder (Input, Textarea, Select)

Gemeinsame Feld-Klassen:
`block w-full rounded-md border-0 py-1.5 px-3 text-slate-900 shadow-1 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand sm:text-sm`

- **Fokus:** 2-px-Ring in `brand`; der native Browser-Outline ist global abgeschaltet (siehe [Globale Basis-Styles](#globale-basis-styles)).
- **Fehler:** `ring-danger-500 focus:ring-danger-500`, Meldung darunter als `mt-1 text-sm text-danger-500`. Bei Fehler wird der Hinweistext ausgeblendet.
- **Label:** `text-sm font-medium text-slate-900`, Pflichtfeld-Stern `text-danger-500`.
- **Hinweis:** `mt-1 text-sm text-slate-500`.
- Textarea: Standard 3 Zeilen.

### Badge

Basis: `inline-flex items-center rounded-pill px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset`

| Ton | Klassen | Beispiele |
|---|---|---|
| Neutral | `bg-slate-100 text-slate-600 ring-slate-300/40` | Neu, Inaktiv |
| Erfolg | `bg-success-500/10 text-success-500 ring-success-500/20` | Aktiv, Fertig, Exportiert |
| Info | `bg-trust-500/10 text-trust-500 ring-trust-500/20` | In Bearbeitung |
| Warnung | `bg-warning-500/10 text-warning-500 ring-warning-500/20` | Offen, Im Review, Priorität hoch |

Kompakte Variante (z. B. neben Feld-Labels): `px-2 py-0.5 text-[11px]`.

### Alert

Basis: `rounded-md p-4 text-sm font-medium ring-1 ring-inset`

| Typ | Klassen |
|---|---|
| `info` (Standard) | `bg-trust-500/10 text-trust-500 ring-trust-500/20` |
| `success` | `bg-success-500/10 text-success-500 ring-success-500/20` |
| `warning` | `bg-warning-500/10 text-warning-500 ring-warning-500/20` |
| `error` | `bg-danger-500/10 text-danger-500 ring-danger-500/20` |

Erfolgs- und Fehlermeldungen nach Aktionen erscheinen als Alert oben im Inhaltsbereich.

### Modal

- Overlay: `fixed inset-0 bg-ink-900/50`, Klick schließt
- Dialog: `rounded-lg bg-white p-6 shadow-3`, zentriert, Standardbreite `max-w-md`, Inhalt bei Überlänge scrollbar
- `role="dialog" aria-modal="true"`

### Lade-Overlay

Vollbild-Overlay für länger laufende Aktionen (in der Quelle: KI- und API-Aufrufe).

- Overlay wie Modal, nicht schließbar
- Box: `max-w-sm rounded-lg bg-white p-8 text-center shadow-3`, Inhalte zentriert mit `gap-3`
- Spinner: Icon `refresh`, 28 px, `animate-spin text-brand`
- Titel `text-base font-semibold text-slate-900`, optionaler Text `text-sm text-slate-500`
- `role="alert" aria-live="assertive"`

### Toggle

- Track: `h-6 w-11 rounded-pill transition-colors`, aktiv `bg-brand`, inaktiv `bg-slate-200`
- Knopf: `h-4 w-4 rounded-pill bg-white transition-transform`, inaktiv `translate-x-1`, aktiv `translate-x-6`
- Label `text-sm font-medium text-slate-900`, Hinweis `text-xs text-slate-500`
- `role="switch"` mit `aria-checked`

### Tabelle

Tabellen stehen in einer Karte.

- `<table class="min-w-full divide-y divide-slate-100">`
- Kopfzelle: `px-6 py-3 text-left text-xs font-semibold text-slate-500`
- Zelle: `px-6 py-4 text-sm`
- Primäre Spalte (verlinkter Name): `text-brand`, `font-medium` bzw. `font-semibold` für übergeordnete Einträge

### Navigation

**Hauptnavigation (Sidebar):**

- Zeile: `flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium`, Icon 18 px
- Aktiv: `bg-brand-tint text-brand-700`
- Inaktiv: `text-slate-700 hover:bg-slate-50`
- Abschnitts-Label: `px-3 pt-5 text-xs font-semibold uppercase tracking-wide text-slate-400`

**Unternavigation** (eingerückt unter dem aktiven Hauptpunkt):

- Container: `ml-4 space-y-0.5 border-l border-slate-200 pl-4`
- Zeile: `rounded-md px-2 py-1.5 text-sm`
- Aktiv: `font-medium text-brand-700`; inaktiv: `text-slate-500 hover:text-slate-900`

---

## Layout-Shell

```
┌────────────┬──────────────────────────────────────────┐
│ App-Name   │ Seitentitel                    (60 px)   │
│  (60 px)   ├──────────────────────────────────────────┤
│            │                                          │
│ Navigation │   Inhalt, zentriert, max. Breite         │
│            │   Karten mit space-y-6                   │
│  250 px    │                                          │
│            │                                          │
│ Fußzeile   │                                          │
└────────────┴──────────────────────────────────────────┘
```

| Bereich | Spezifikation |
|---|---|
| Seite | `bg-slate-100`, `min-h-screen`, Flex-Zeile |
| Sidebar | `w-[250px]`, `bg-white`, `border-r border-slate-200`; unter `md` (768 px) ausgeblendet |
| Sidebar-Kopf | Höhe `60px`, `px-6`, App-Name in `font-display text-lg font-semibold` |
| Sidebar-Navigation | `space-y-1 px-3` |
| Sidebar-Fuß | `border-t border-slate-200 p-4 text-xs text-slate-400` |
| Topbar | Höhe `60px`, `bg-white`, `border-b border-slate-200`, `px-4 md:px-8` |
| Inhalt | `px-4 py-8 md:px-8`, zentrierter Container mit `space-y-6` |
| Inhaltsbreite | Standard `max-w-3xl` (Formulare), `max-w-5xl` (breite Inhalte), `max-w-6xl` (Tabellen/Übersichten) |

Zielbreiten: 375 px (mobil), 768 px (Tablet), 1440 px (Desktop).

---

## Globale Basis-Styles

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  body {
    @apply bg-slate-100 text-slate-900 font-body antialiased;
  }

  h1, h2, h3, h4 {
    @apply font-display tracking-tight;
  }

  ::selection {
    @apply bg-brand-200 text-ink-900;
  }

  ::-webkit-scrollbar {
    width: 11px;
    height: 11px;
  }

  ::-webkit-scrollbar-thumb {
    @apply bg-slate-300 border-4 border-slate-100;
    border-radius: 9px;
  }

  ::-webkit-scrollbar-thumb:hover {
    @apply bg-slate-400;
  }

  /* Felder zeigen bei Fokus einen Tailwind-Ring; ohne dieses Reset käme der
     native Browser-Outline zusätzlich dazu (doppelter Rahmen). */
  input, textarea, select {
    @apply focus:outline-none;
  }
}
```

---

## Setup in einer neuen App

### Tailwind CSS v3

`theme.extend` in die `tailwind.config` übernehmen:

```js
module.exports = {
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#fff5ef',
          100: '#ffe6d5',
          200: '#ffc9a8',
          300: '#ffa979',
          400: '#ff8b51',
          500: '#fe7437',
          600: '#e8612a',
          700: '#cf5421',
          800: '#a4421a',
          900: '#7a3214',
          hover: '#e8612a',
          press: '#cf5421',
          tint: '#fff5ef',
          DEFAULT: '#fe7437',
        },
        sun: { 500: '#ff9900' },
        ink: {
          800: '#1d2026',
          900: '#15191f',
        },
        slate: {
          50: '#f8f9fb',
          100: '#f3f4f6',
          200: '#e3e6ea',
          300: '#c4c9d1',
          400: '#9098a3',
          500: '#8a8f9a',
          600: '#6a7080',
          700: '#4a4f59',
          800: '#2f343d',
          900: '#1d2026',
        },
        cream: { 100: '#faf7f2' },
        success: { 500: '#2ea562' },
        warning: { 500: '#f6a623' },
        danger: { 500: '#e0394f' },
        trust: { 500: '#1b4ebd' },
      },
      borderRadius: {
        sm: '4px',
        md: '8px',
        lg: '12px',
        xl: '16px',
        pill: '9999px',
      },
      boxShadow: {
        1: '0 1px 2px rgba(15,17,21,.05), 0 1px 1px rgba(15,17,21,.04)',
        2: '0 10px 24px -8px rgba(15,17,21,.14)',
        3: '0 24px 48px -12px rgba(15,17,21,.25)',
      },
      fontFamily: {
        display: ["'Bricolage Grotesque'", "'Manrope'", 'system-ui', 'sans-serif'],
        body: ["'Manrope'", 'system-ui', 'sans-serif'],
        mono: ["'JetBrains Mono'", 'monospace'],
      },
    },
  },
};
```

Hinweis: `slate` überschreibt Tailwinds Standard-Slate; `borderRadius` überschreibt `sm`/`md`/`lg`/`xl` mit den oben genannten Werten.

### CSS-Variablen (ohne Tailwind)

```css
:root {
  /* Brand */
  --brand-50: #fff5ef;
  --brand-100: #ffe6d5;
  --brand-200: #ffc9a8;
  --brand-300: #ffa979;
  --brand-400: #ff8b51;
  --brand-500: #fe7437;
  --brand-600: #e8612a;
  --brand-700: #cf5421;
  --brand-800: #a4421a;
  --brand-900: #7a3214;
  --brand: var(--brand-500);
  --brand-hover: var(--brand-600);
  --brand-press: var(--brand-700);
  --brand-tint: var(--brand-50);

  /* Neutral */
  --slate-50: #f8f9fb;
  --slate-100: #f3f4f6;
  --slate-200: #e3e6ea;
  --slate-300: #c4c9d1;
  --slate-400: #9098a3;
  --slate-500: #8a8f9a;
  --slate-600: #6a7080;
  --slate-700: #4a4f59;
  --slate-800: #2f343d;
  --slate-900: #1d2026;
  --ink-800: #1d2026;
  --ink-900: #15191f;

  /* Status & Akzente */
  --success-500: #2ea562;
  --warning-500: #f6a623;
  --danger-500: #e0394f;
  --trust-500: #1b4ebd;
  --sun-500: #ff9900;
  --cream-100: #faf7f2;

  /* Radius */
  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  --radius-pill: 9999px;

  /* Schatten */
  --shadow-1: 0 1px 2px rgba(15,17,21,.05), 0 1px 1px rgba(15,17,21,.04);
  --shadow-2: 0 10px 24px -8px rgba(15,17,21,.14);
  --shadow-3: 0 24px 48px -12px rgba(15,17,21,.25);

  /* Schriften */
  --font-display: 'Bricolage Grotesque', 'Manrope', system-ui, sans-serif;
  --font-body: 'Manrope', system-ui, sans-serif;
  --font-mono: 'JetBrains Mono', monospace;

  /* Layout */
  --sidebar-width: 250px;
  --topbar-height: 60px;
}
```

### Checkliste

1. Google-Fonts-`<link>` in den `<head>` aufnehmen.
2. Tailwind-`theme.extend` (oder die CSS-Variablen) übernehmen.
3. Globale Basis-Styles in das Haupt-Stylesheet kopieren.
4. Icon-Komponente mit den Pfaddaten aus der [Icon-Tabelle](#icon-set) anlegen.
5. Basis-Komponenten (Button, Felder, Badge, Alert, Modal, Toggle, Karte) nach den Klassenangaben oben aufbauen.
6. Layout-Shell mit 250-px-Sidebar und 60-px-Topbar einrichten.
