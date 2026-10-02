# Customer Service Assist

> A Laravel app, built on the AI Coding Starter Kit, with an AI-powered development workflow using specialized skills for Requirements, Architecture, Frontend, Backend, QA, and Deployment.

## Tech Stack

| Category | Tool | Why? |
|----------|------|------|
| **Framework** | Laravel 13 (PHP 8.5) | Full-stack MVC, batteries included |
| **Templating** | Blade | Server-side templating with reusable component system |
| **Styling** | Tailwind CSS v4 | Utility-first CSS, configured CSS-first in `resources/css/app.css` (`@theme`) — no `tailwind.config.js` |
| **JS Framework** | Alpine.js | Lightweight reactive behavior in Blade templates |
| **Database** | MySQL | Relational database, managed by Eloquent ORM |
| **Validation** | Laravel Form Requests | Request validation + authorization in one class |
| **Testing** | Laravel Pest | Expressive PHP testing framework |
| **Auth** | Laravel Fortify | Headless auth backend with custom Blade views (Breeze is no longer used) |
| **AI Tooling** | Laravel Boost | MCP server + Laravel guidelines for AI agents |
| **Local Dev** | Laravel Sail (Docker), PHP 8.5, Node 24 | Containerized, no local PHP/Composer/Node needed |

## Project Structure

```
app/
  Http/
    Controllers/      # MVC Controllers
    Requests/         # Form Request classes (validation + authorization)
    Middleware/        # Laravel Middleware
  Models/             # Eloquent Models
  Policies/           # Authorization Policies
  Providers/          # Service Providers (AppServiceProvider)
bootstrap/
  app.php             # Routing, middleware and exception configuration
resources/
  views/              # Blade templates
    layouts/          # Master layouts
    components/       # Reusable Blade components (<x-name>)
  js/                 # Alpine.js files
  css/                # Tailwind CSS entry point
  lang/               # Translations
routes/
  web.php             # Web routes (return Blade views)
  api.php             # API routes (return JSON)
database/
  migrations/         # DB schema migrations
  factories/          # Model factories (for tests/seeders)
  seeders/            # DB seeders
tests/
  Feature/            # Pest feature tests (HTTP layer tests)
  Unit/               # Pest unit tests (isolated logic)
config/               # Laravel config files
storage/              # Logs, cache, file uploads
.env.example          # Environment variable template (Sail ports, DB config)
compose.yaml          # Laravel Sail / Docker services (app + mysql)
```

## Local Development (Docker / Sail only)

- This project runs **exclusively via Docker + Laravel Sail** — no local PHP, Composer, or Node installation is assumed or required.
- **Laravel Herd is not used for this project.**
- On Windows, all development happens inside **WSL2/Ubuntu** — never directly in PowerShell or CMD. Ideally the repo lives in the WSL filesystem (e.g. `~/code/your-project`), not under `/mnt/c/Users/...`.
- Always run project commands through `./vendor/bin/sail ...` (artisan, composer, npm, pest) rather than bare `php`/`composer`/`npm`.
- Ports default to Sail's standard values (`APP_PORT=80`, `VITE_PORT=5173`, `FORWARD_DB_PORT=3306`). If multiple Sail projects run in parallel, override these in `.env`/`.env.example` to avoid collisions — see README for details.
- Feature development should only start once `/init` has defined the actual product (`docs/PRD.md`) and this base Sail installation is documented and runnable.

## Laravel Boost (MCP)

- `laravel/boost` is installed as a dev dependency; its MCP server is registered in `.mcp.json` and runs through Sail (`vendor/bin/sail artisan boost:mcp`), so the containers must be up.
- Prefer the Boost tools over guessing: search the version-specific Laravel docs, inspect the database schema, read application/browser logs, and run code via Tinker.
- Project-specific AI guidelines go in `.ai/guidelines/*.md`; run `./vendor/bin/sail artisan boost:update` after changing them or after dependency upgrades.

## Development Workflow

1. `/init` — Initialize the project: PRD + feature map (run once at the start)
2. `/write-spec` — Create a full feature spec for one feature
3. `/architecture` — Design tech architecture (PM-friendly, no code)
4. `/frontend` — Build UI with Blade templates and Alpine.js
5. `/backend` — Build controllers, Eloquent models, migrations, Form Requests
6. `/qa` — Test against acceptance criteria + security audit + Pest tests
7. `/deploy` — Set up Sail locally, production-ready checks

Use `/refine PROJ-X` at any point to revisit and improve an existing feature spec.

## Feature Tracking

All features tracked in `features/INDEX.md`. Every skill reads it at start and updates it when done. Feature specs live in `features/PROJ-X-name.md`.

## Key Conventions

- **Feature IDs:** PROJ-1, PROJ-2, etc. (sequential)
- **Commits:** `feat(PROJ-X): description`, `fix(PROJ-X): description`
- **Single Responsibility:** One feature per spec file
- **Blade components first:** check `resources/views/components/` before creating new ones. Reuse via `<x-component-name>`.
- **Form Requests for all validation:** NEVER validate in controllers directly
- **`$fillable` on every model:** prevents mass assignment vulnerabilities
- **Human-in-the-loop:** All workflows have user approval checkpoints
- **Pest tests:** Feature tests in `tests/Feature/` (HTTP layer), Unit tests in `tests/Unit/`

## Build & Test Commands

All commands run through Sail (no local PHP/Composer/Node):

```bash
# Containers
./vendor/bin/sail up -d
./vendor/bin/sail down

# Development
./vendor/bin/sail artisan migrate           # Run pending migrations
./vendor/bin/sail artisan make:model X -mf  # Create model, migration, factory
./vendor/bin/sail artisan make:request StoreXRequest  # Create Form Request
./vendor/bin/sail artisan make:policy XPolicy --model=X  # Create Policy
./vendor/bin/sail artisan route:list        # Show all registered routes

# Testing
./vendor/bin/sail pest                      # Run all Pest tests
./vendor/bin/sail pest --filter=FeatureName # Run specific test

# Assets
./vendor/bin/sail npm run dev               # Vite asset dev server
./vendor/bin/sail npm run build             # Production asset build
```

## Product Context

@docs/PRD.md

## Feature Overview

@features/INDEX.md

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `vendor/bin/sail composer show --direct` to list direct dependencies with versions, or `vendor/bin/sail composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `vendor/bin/sail artisan route:list`). Use `vendor/bin/sail artisan list` to discover available commands and `vendor/bin/sail artisan [command] --help` to check parameters.
- Inspect routes with `vendor/bin/sail artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `vendor/bin/sail artisan config:show app.name`, `vendor/bin/sail artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `vendor/bin/sail artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `vendor/bin/sail artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== sail rules ===

# Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `vendor/bin/sail artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test --format agent`, simply run `vendor/bin/sail bin pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `vendor/bin/sail artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `vendor/bin/sail artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/sail bin pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `vendor/bin/sail artisan test --compact`.

</laravel-boost-guidelines>
