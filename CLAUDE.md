# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Pravila rada (obavezno)

- **Uvek prvo objasni plan i pitaj za potvrdu pre bilo kakve izmene.** Ne menjaj kod dok korisnik ne odobri plan.
- **Ne menjaj više od jednog fajla bez izričite dozvole.** Ako zadatak zahteva izmene u više fajlova, prvo objasni šta i gde, pa traži potvrdu.
- **Ako nešto nije jasno, pitaj korisnika umesto da pretpostaviš.**
- **Communicate in English** (the user is practicing English). If the user does not understand something, they will ask for a clarification in Serbian — only then explain in Serbian.

## What this is

Internal admin panel / PWA for **Ekošarna D.O.O.** (a Serbian electrical-works company). It manages contact-form leads from the public site, work scheduling, job sites (gradilišta), tasks, time tracking, warehouse/procurement, an address book, and team accounts. The UI language is **Serbian** — code identifiers, table/column names, routes, and comments are all in Serbian, and user-facing strings should stay in Serbian to match.

This is a hand-rolled PHP MVC app (no framework, no Composer/`vendor/`). It runs on shared cPanel hosting (Apache + PHP + MySQL) in production and XAMPP locally. The app lives in the `mvc/` subdirectory and is served under the `/mvc` base path.

## Running locally

There is no build step, package manager, or test suite. Development is "edit a `.php` file and reload the browser."

- Serve via XAMPP (Apache + MySQL). The app entry point is `mvc/index.php`; reach it at `http://localhost/mvc/`.
- Create a MySQL database and a `.env` file (see `mvc/.env` for the key list: `DB_*`, `SMTP_*`, `IMAP_*`, `VAPID_*`, `TELEGRAM_BOT_TOKEN`). `Core/Config.php` searches for `.env` in three locations, preferring one above the web root.
- Schema: core tables (`admin_korisnici`, `kontakt_forme`, `imenik_*`) are auto-created/migrated on each authenticated request by `Database::migrate()`. The rest of the tables (raspored, magacin, nabavka, evidencija, katalog) are **not** in `migrate()` — import them from the `.sql` files in `mvc/` and the repo root (`raspored_novo.sql`, `magacin_tabele.sql`, `katalog_materijala.sql`, `ekosarna_db.sql`, etc.).
- On an empty `admin_korisnici` table, `migrate()` seeds a first Administrator: username `ekosarna`, password `Ek0s@rna2024!`.

## Architecture

### Request flow
Everything routes through `mvc/index.php` → `Core\Router::dispatch()` (Apache rewrites all non-file requests to `index.php` via the `htaccess` file). The router:
1. Starts the session and handles `?logout=1`.
2. **AJAX dispatch**: if `$_POST['_action']` is set and the user is authenticated, calls `handleAjax()`, which routes by action-name **prefix** (e.g. `zadatak_*` → `ZadaciController`, `raspored_*` → `RasporedController`, `magacin_*` → `MagacinController`) and returns JSON. This is the primary way the frontend mutates data — almost every interactive feature is a `fetch()` POST with an `_action` field, not a REST route.
3. **Page dispatch**: otherwise routes by `?page=...` to a controller's `index()` via a `match` expression, rendering HTML.

So adding a feature usually means: a `?page=` case + controller `index()` for the screen, plus one or more `*_action` branches in both `Router::handleAjax()` and the controller's `ajax($action, $id)` method for the interactions.

### Layers (`app/`)
- `Core/` — framework primitives. `Router`, `Config` (loads `.env` into constants), `Auth` (session + roles), `Database` (PDO singleton + `migrate()`), `Controller` (base class: `view()`, `json()`, `h()`).
- `Controllers/` — one per feature area. Each typically has `index()` (renders a view) and `ajax(string $action, int $id)` (handles the prefixed AJAX actions, echoes JSON).
- `Models/` — static-method classes wrapping PDO queries (`Core\Database as DB`). Not all features have a model; several controllers query the DB directly via `Database::get()`.
- `Views/` — plain PHP templates, one folder per feature. `Controller::view()` wraps the template with `layout/header.php` (sidebar nav, notification/badge polling JS) and `layout/footer.php` (mail modal, file-viewer modal, PWA service-worker registration). Views contain inline `<script>`/`<style>` heavily; shared CSS is `public/css/admin.css` and shared JS is `public/js/app.js`.

Autoloading is a simple `spl_autoload_register` in each entry point mapping `Namespace\Class` → `app/Namespace/Class.php`. Namespaces are `Core\`, `Controllers\`, `Models\`.

### Authentication & roles
`Core\Auth` is session-based. Three roles in `admin_korisnici.uloga`:
- **Administrator** — full access (only role that sees `magacin`, `imenik`, `obavestenja`, `tim`).
- **Operater** — office staff; everything except admin-only pages.
- **Elektricar** — field electrician; restricted by the router to `danas`, `poruke`, `hr`, `evidencija`, `nabavka` only, and defaults to the `danas` screen.

Enforce access with `Auth::requireLogin()` / `requireAdmin()` / `requireKancelarija()` and the `isAdmin()`/`isElektricar()`/`isKancelarija()` checks. The router also gates pages and AJAX prefixes (e.g. `obavestenja_*`/`magacin_*`/`evidencija_*` call `Auth::requireAdmin()`). Sessions auto-expire after 2h of inactivity.

### Secondary entry points (bypass the router)
These are called directly (whitelisted in `htaccess`) and each re-implements the autoloader + Config bootstrap:
- `check_notifications.php`, `badge_counts.php` — polled by the frontend (every 10s / 60s) for unread message counts and sidebar badge counts.
- `push_cron.php`, `telegram_cron.php` — daily cron jobs (08:00) that send task reminders via Web Push (VAPID) and Telegram. Configured as cPanel cron jobs.
- `telegram_webhook.php`, `telegram_link.php` — Telegram bot webhook + account linking.
- `sw.js` — PWA service worker (needs `Service-Worker-Allowed: /`, set in `htaccess`).

## Conventions & gotchas

- **Always escape output** with the global `h()` helper (or `Controller::h()`) when echoing user data into HTML.
- **Always use prepared statements** with bound params (PDO is configured with `EMULATE_PREPARES => false`). Note: `LIMIT`/`OFFSET` are interpolated as cast ints in some models — keep that pattern (don't bind them) and ensure they're cast.
- New AJAX actions must be registered in **two** places: the prefix router in `Router::handleAjax()` and the controller's `ajax()` switch.
- `Database::migrate()` adds new columns via a list of best-effort `ALTER TABLE ... ADD COLUMN` statements wrapped in try/catch (the catch swallows "column already exists"). Follow that pattern to evolve the core tables without a migration tool.
- The DB charset is `utf8mb4` throughout — required for Serbian Latin/Cyrillic and emoji.
- Beware existing dead/buggy helpers (e.g. `KontaktForma::countUnread()` is broken; use `getUnreadCount()`). Don't copy a method just because it exists — check it returns sensibly.
- Uploaded files go under `uploads/` and `public/uploads/`; PHPMailer is vendored in `phpmailer/`, web-push in `webpush/` (no Composer).
- Do not commit `.env` or real credentials. The `.env` checked into the repo root contains placeholder/`123` values; a prior commit explicitly removed `.env` from a public folder for security.
