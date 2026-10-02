# Customer Service Assist

> Basiert auf dem AI Coding Starter Kit — Laravel Sail Edition. Build production-ready Laravel apps faster with AI-powered Skills handling Requirements, Architecture, Development, QA, and Deployment — running entirely on Docker + Laravel Sail inside WSL2.

Dieses Template nutzt [Claude Code](https://docs.anthropic.com/en/docs/claude-code) mit Skills, Rules und Sub-Agents für einen kompletten AI-unterstützten Entwicklungsworkflow — zugeschnitten auf den **Laravel / Blade / Tailwind / Alpine.js / MySQL / Pest**-Stack, lokal ausschließlich über **Docker + Laravel Sail + WSL2** betrieben. Siehe [AI-Powered Development Workflow](#ai-powered-development-workflow) unten.

## Technischer Stack

| Category | Tool | Why? |
|----------|------|------|
| **Framework** | Laravel 13 (PHP 8.5) | Full-stack MVC, batteries included |
| **Templating** | Blade | Server-side templating mit Components |
| **Styling** | Tailwind CSS v4 | Utility-first CSS, CSS-first-Konfiguration (`@theme` in `resources/css/app.css`) |
| **JS Framework** | Alpine.js | Leichtgewichtige Reaktivität, kein SPA-Overhead |
| **Database** | MySQL (im Container) | Relationale Datenbank via Eloquent ORM |
| **Validation** | Laravel Form Requests | Validation + Authorization in einer Klasse |
| **Testing** | Laravel Pest | Ausdrucksstarkes PHP-Testing |
| **Auth** | Laravel Fortify | Headless Auth-Backend mit eigenen Blade-Views |
| **AI Tooling** | Laravel Boost | MCP-Server + Laravel-Guidelines für AI-Agents |
| **Build** | Vite 8 + `@tailwindcss/vite` | Asset-Build ohne PostCSS-Konfiguration |
| **Local Dev** | **Laravel Sail (Docker) — ausschließlich** | Containerisiert, kein lokales PHP/Composer/Node nötig |

> **Laravel Herd wird für dieses Projekt nicht verwendet.** Die lokale Entwicklung läuft ausschließlich über Docker + Laravel Sail innerhalb von WSL2/Ubuntu.

## Voraussetzungen

- Windows 11
- WSL2 mit Ubuntu (`wsl --install` bzw. vorhandene Ubuntu-Distribution)
- Docker Desktop mit aktiviertem WSL2-Backend
- Git
- VS Code mit der Extension **Remote Development / WSL** (empfohlen)

Alle Befehle unten werden **im WSL2-/Ubuntu-Terminal** ausgeführt — **nicht** in PowerShell oder CMD. Das Projekt sollte idealerweise im WSL-Dateisystem liegen (z. B. `~/code/your-project`), nicht unter `/mnt/c/Users/...`, da das deutlich performanter ist.

## Installation unter Windows + WSL2

```bash
cd ~/code
git clone https://github.com/looxis/customerservice-assist.git customerservice-assist
cd customerservice-assist
cp .env.example .env
```

Vor dem ersten `sail up` existiert `./vendor/bin/sail` noch nicht — die PHP-Abhängigkeiten werden daher einmalig über einen Wegwerf-Container installiert (kein lokales PHP/Composer nötig). Die App nutzt **PHP 8.5** — dafür wird das offizielle `composer`-Image verwendet (ein `laravelsail/php85-composer`-Image gibt es nicht):

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/app" \
    -w /app \
    composer:latest \
    composer install --ignore-platform-reqs
```

Danach läuft alles über Sail:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Die App ist danach erreichbar unter **http://localhost:8081**.

### Laravel Boost einrichten (einmalig)

[Laravel Boost](https://github.com/laravel/boost) ist bereits als Dev-Dependency enthalten und der MCP-Server in `.mcp.json` für Sail eingetragen. Guidelines und Skills für Claude Code installierst du einmalig interaktiv:

```bash
./vendor/bin/sail artisan boost:install
```

Im Dialog **Claude Code** und **Laravel Sail** auswählen. Boost schreibt seine Guidelines in einen eigenen `<laravel-boost-guidelines>`-Block in `CLAUDE.md`; der Rest der Datei bleibt erhalten. Der MCP-Server läuft im Container — die Sail-Container müssen also gestartet sein, und Claude Code muss in WSL laufen. Nach Dependency-Upgrades: `./vendor/bin/sail artisan boost:update`.

## Skeleton per `laravel new` neu erzeugen oder abgleichen

Laravel-Projekte werden inzwischen über den Installer gestartet (`laravel new`, siehe [Installation](https://laravel.com/framework/docs/installation)). Dieses Repo enthält das Ergebnis bereits fertig konfiguriert. Um das Skeleton später auf einen frischen Stand zu bringen, erzeugst du in einem Wegwerf-Container ein Referenzprojekt und übernimmst die Unterschiede:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/app" \
    -w /app \
    composer:latest \
    sh -c 'composer global require laravel/installer \
        && "$(composer global config bin-dir --absolute -q)/laravel" new reference-app \
            --database=mysql --pest --no-authentication --no-node --boost --no-interaction'
```

Die Flags entsprechen dem Stack dieses Kits: MySQL, Pest, kein Starter Kit (Blade + Alpine statt React/Vue/Svelte/Livewire), Boost. Danach `reference-app/` mit diesem Repo vergleichen (`composer.json`, `package.json`, `vite.config.js`, `config/`, `bootstrap/`) und das Verzeichnis wieder löschen. Kit-spezifisch bleiben: `.claude/`, `CLAUDE.md`, `docs/`, `features/`, `compose.yaml`, Alpine.js und Pest.

Mit lokalem PHP (nicht der Standardweg dieses Kits) installiert `php.new` PHP 8.5, Composer und den Installer in einem Schritt:

```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

> Die Agent-Anleitung unter https://laravel.com/for/agents geht von anderen Defaults aus (React Starter Kit, SQLite, `composer run dev` auf Port 8000). In diesem Kit gelten stattdessen Blade + Alpine, MySQL und Sail auf `http://localhost`.

## Ports

Standardmäßig nutzt dieses Template die Sail-Standardports (siehe `.env.example`):

| Service | Port |
|---------|------|
| Laravel (App) | `http://localhost` (Port `80`) |
| Vite (Dev-Server) | `5173` |
| MySQL (extern, z. B. für einen DB-Client) | `3306` |

Falls mehrere Sail-Projekte parallel laufen sollen, in `.env` (und ggf. `.env.example`) `APP_PORT`, `VITE_PORT` und `FORWARD_DB_PORT` projektspezifisch anpassen — `compose.yaml` liest diese Werte bereits automatisch.

## Täglicher Workflow

```bash
cd ~/code/customerservice-assist
./vendor/bin/sail up -d
./vendor/bin/sail npm run dev
```

Stoppen:

```bash
./vendor/bin/sail down
```

## Nützliche Sail-Befehle

```bash
./vendor/bin/sail artisan <befehl>
./vendor/bin/sail composer <befehl>
./vendor/bin/sail npm <befehl>
./vendor/bin/sail pest
./vendor/bin/sail shell
./vendor/bin/sail logs
```

---

## AI-Powered Development Workflow

Dieses Repo basiert auf dem AI Coding Starter Kit (Laravel Edition) und nutzt Claude Code Skills, um Requirements, Architecture, Frontend, Backend, QA und Deployment strukturiert zu begleiten.

### Initialize Your Project

Falls `docs/PRD.md` noch ein leeres Template ist, starte mit `/init` und einer kurzen Beschreibung deiner Idee:

```
/init I want to build a project management tool for small teams
where users can create projects, assign tasks, and track progress.
```

Die Skill interviewt dich Schritt für Schritt (**Grill Me**-Prinzip — immer mit einer Empfehlung, die du bestätigst oder korrigierst), bis ein gemeinsames Verständnis besteht. Danach:
1. Erstellt sie dein **Product Requirements Document** (`docs/PRD.md`)
2. Bricht das Projekt in eine priorisierte Feature-Map herunter (P0/P1/P2)
3. Entscheidet über Auth (Laravel Fortify) und Design System
4. Aktualisiert das **Feature Tracking** (`features/INDEX.md`)
5. Empfiehlt das erste zu bauende Feature

### Spec Your First Feature

```
/write-spec PROJ-1
```

Die Skill interviewt dich im Detail zu genau diesem Feature — User Stories, Edge Cases, Acceptance Criteria im deutschen Angenommen/Wenn/Dann-Format. Mit `/refine PROJ-X` kannst du einen bestehenden Spec jederzeit weiter verfeinern.

### Build Features

```
/architecture    Design the tech approach for features/PROJ-1-....md
/frontend        Build the Blade UI for features/PROJ-1-....md
/backend         Build controllers, models, migrations for features/PROJ-1-....md
/qa              Test features/PROJ-1-....md
/deploy          Deploy to production
```

Jede Skill schlägt am Ende den nächsten Schritt vor. Handoffs erfolgen immer durch den Nutzer.

## Available Skills

| Skill | Command | What It Does |
|-------|---------|-------------|
| Project Initializer | `/init` | One-time setup: creates PRD + feature map via Grill Me interview |
| Feature Spec Writer | `/write-spec` | Creates a full spec for one feature (user stories, AC, edge cases) |
| Spec Refiner | `/refine PROJ-X` | Reopens an existing spec to improve, extend, or challenge it |
| Solution Architect | `/architecture` | Designs PM-friendly tech architecture (no code, only high-level design) |
| Frontend Developer | `/frontend` | Builds UI with Blade templates, Alpine.js, and Tailwind CSS |
| Backend Developer | `/backend` | Builds controllers, Eloquent models, migrations, Form Requests |
| QA Engineer | `/qa` | Tests features against acceptance criteria + writes Pest tests + security audit |
| DevOps | `/deploy` | Guides through (local) deployment checks |
| Help | `/help` | Context-aware guide: shows where you are and what to do next |

### How Skills Work

- **Skills** are defined in `.claude/skills/` and auto-discovered by Claude Code
- **Rules** in `.claude/rules/` are auto-applied based on file context (no manual loading)
- **Sub-Agents** run heavy tasks (frontend, backend, QA) in isolated contexts for cost efficiency
- **CLAUDE.md** provides project context automatically at every session start

---

## Development Workflow

```
0. Setup     /init          -->  PRD + feature map (once per project)
1. Spec      /write-spec    -->  Feature spec in features/PROJ-X.md
             /refine PROJ-X -->  Revisit and improve an existing spec
2. Design    /architecture  -->  Tech design added to feature spec
3. Build     /frontend      -->  Blade views + Alpine.js implemented
             /backend       -->  Controllers + models + migrations (if needed)
4. Test      /qa            -->  Pest tests + security audit
5. Ship      /deploy        -->  Deployed to production
```

### Feature Tracking

Features sind in `features/INDEX.md` getrackt. Jede Skill liest diese Datei bei Start und aktualisiert sie nach Abschluss, um Doppelarbeit zu vermeiden.

---

## Project Structure

```
your-project/
+-- CLAUDE.md                        <-- Auto-loaded project context
+-- compose.yaml                     <-- Sail/Docker services (app + mysql)
+-- .mcp.json                        <-- Laravel Boost MCP server (via Sail)
+-- .claude/
|   +-- settings.json                <-- Team permissions (committed)
|   +-- settings.local.json          <-- Personal overrides (gitignored)
|   +-- rules/                       <-- Auto-applied coding rules
|   +-- skills/                      <-- Invocable workflows (/command)
|   +-- agents/                      <-- Sub-agent configs
+-- features/                        <-- Feature specifications
|   +-- INDEX.md                         Status tracking
+-- docs/
|   +-- PRD.md                       <-- Product Requirements Document
|   +-- production/                  <-- Production setup guides
+-- app/
|   +-- Http/Controllers/            <-- MVC Controllers
|   +-- Http/Requests/               <-- Form Request classes
|   +-- Models/                      <-- Eloquent Models
+-- resources/
|   +-- views/                       <-- Blade templates
|   +-- js/                          <-- Alpine.js
|   +-- css/                         <-- Tailwind CSS
+-- routes/
|   +-- web.php                      <-- Web routes
|   +-- api.php                      <-- API routes
+-- database/
|   +-- migrations/                  <-- Schema migrations
|   +-- factories/                   <-- Model factories
+-- tests/
|   +-- Feature/                     <-- Pest Feature tests
|   +-- Unit/                        <-- Pest Unit tests
+-- .env.example                     <-- Environment variable template (Sail-Ports, DB)
```

---

## How It Works Under the Hood

### Skills (`.claude/skills/`)
Each skill is a structured workflow that Claude Code discovers automatically. Skills can run inline (in the main conversation) or as forked sub-agents (isolated context window).

| Skill | Execution | Why? |
|-------|-----------|------|
| `/init` | Inline | Needs live interview with user |
| `/write-spec` | Inline | Needs live interview with user |
| `/refine` | Inline | Needs live interview with user |
| `/architecture` | Inline | Short output, user reviews in real-time |
| `/frontend` | Sub-agent (forked) | Heavy file editing, lots of output |
| `/backend` | Sub-agent (forked) | Heavy file editing, migrations, controllers |
| `/qa` | Sub-agent (forked) | Systematic testing, lots of output |
| `/deploy` | Inline | Deployment needs user oversight |
| `/help` | Inline | Quick status check and guidance |

### Rules (`.claude/rules/`)
Coding standards that are auto-applied based on which files Claude is working with. No manual loading needed.

### Sub-Agent Configs (`.claude/agents/`)
Lightweight configurations that define model, tool access, and turn limits for forked skills.

### CLAUDE.md
Auto-loaded at every session start. Contains tech stack, conventions, and references to PRD and feature index.

---

## Context Engineering

AI agents work best with clean, structured context — not longer prompts. This template is designed around these principles:

### State lives in files, not in memory

Every skill reads `features/INDEX.md` and the relevant feature spec at start. After context compaction or a new session, nothing is lost — the agent simply re-reads the files.

### Context is layered

| Layer | What | When loaded |
|-------|------|--------------|
| `CLAUDE.md` | Tech stack, conventions, commands | Every session (auto) |
| `.claude/rules/` | Coding standards | When editing matching files (auto) |
| Skill `SKILL.md` | Workflow instructions | When skill is invoked |
| Feature spec | Requirements, AC, tech design | On demand (skill reads it) |
| `docs/production/` | Deployment guides | Only when referenced |

### Context is isolated

Heavy implementation skills (`/frontend`, `/backend`, `/qa`) run as **forked sub-agents** with their own context window. Each fork starts clean and loads only what it needs.

### Context recovery is built in

All forked skills include a **Context Recovery** section: if the context is compacted mid-task, the agent re-reads the feature spec, checks `git diff` for progress, and continues without restarting.

---

## Customization for Your Team

1. **Edit CLAUDE.md** — Add project-specific conventions
2. **Edit docs/PRD.md** — Define your product vision and roadmap
3. **Edit .claude/rules/** — Adjust coding standards for your team
4. **Edit .claude/skills/** — Modify workflows to match your process
5. **Edit .claude/settings.json** — Configure team permissions

---

## Production Guides

Standalone guides in `docs/production/`:

| Guide | Setup Time | What It Does |
|-------|-----------|-------------|
| [Error Tracking](docs/production/error-tracking.md) | 5 min | Sentry for Laravel — automatic error capture |
| [Security Headers](docs/production/security-headers.md) | 2 min | SecurityHeaders middleware (XSS, Clickjacking) |
| [Performance](docs/production/performance.md) | 10 min | Lighthouse, caching, Eloquent optimization |
| [Database Optimization](docs/production/database-optimization.md) | 15 min | Indexing, N+1 prevention, query caching |
| [Rate Limiting](docs/production/rate-limiting.md) | 5 min | Laravel throttle middleware |

---

## Scripts

```bash
./vendor/bin/sail up -d            # Container starten
./vendor/bin/sail down             # Container stoppen
./vendor/bin/sail artisan migrate  # Migrationen ausführen
./vendor/bin/sail pest             # Pest-Testsuite ausführen
./vendor/bin/sail artisan route:list  # Alle Routen anzeigen
./vendor/bin/sail npm run dev      # Vite Dev-Server (Hot Reload)
./vendor/bin/sail npm run build    # Production-Asset-Build
```

---

## License

MIT License — feel free to use for your projects!
