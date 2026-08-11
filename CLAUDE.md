# SynergyCP rDNS Package

## Overview

This package adds reverse DNS (PTR record) management to SynergyCP. It supports multiple DNS providers: SynergyCP API, PowerDNS v3, PowerDNS v4, and Cloudflare.

## Architecture

- **Strategy pattern**: All DNS providers implement `IServerControl` (two methods: `createPtr`, `deletePtr`).
- **Factory**: `ServerService` resolves the correct provider class based on the `pkg.rdns.api.type` setting. It builds the shared Guzzle client with connect/request timeouts and throws on an unrecognized (non-empty) provider type instead of silently falling back.
- **Event-driven**: PTR operations are dispatched as Laravel events and handled by queued listeners (`SyncToDnsServer`, `DeleteFromDnsServer`). Both listeners retry with backoff and have a `failed()` handler that writes an admin-visible log entry when retries are exhausted.
- **Logging**: Event classes (`PtrCreated`, `PtrDeleted`, `PtrPtrUpdated`) implement `LoggableEvent` and are logged by the parent app's `EventLogger`. Providers can return a string from `createPtr()` to append info to the log (e.g., Cloudflare returns nameserver details when a zone is created).
- **Zone import**: `app/Ptr/Zone/ZoneController.php` parses BIND-style reverse zone files (IPv4 `/8`–`/24` and IPv6 `ip6.arpa` zones, relative or fully-qualified record names, optional TTL/class, multiple `$ORIGIN` sections). Records are collected and imported in one batch via `PtrService::createMany()`, which uses a fixed number of queries per import (chunked whereIn existence check, one entities query per IP family, chunked bulk inserts) instead of several per record.
- **Admin UI**: The package's Angular module (`pkg.rdns`) decorates the parent app's `SettingsTab` factory to conditionally hide settings fields based on provider type (e.g., API Host and Name Servers are hidden for Cloudflare).
- **Per-server rDNS page**: Both themes register `app.hardware.server.view.rdns` (`/hardware/server/:id/rdns`) as a child of the parent app's abstract server view state. Packages are not limited to `app.pkg.*` states: `RouteHelpersProvider.state` is the raw `$stateProvider.state`, and package JS is loaded via `$futureStateProvider` before the router's first URL sync, so deep links resolve. The server page itself shows a small link panel (registered via `ServerManageProvider.panels.left.after("notes", ...)` — panels are the only registration hook the server page offers). The page controller loads the server via the API itself because `ServerManage.getServer()` is only populated by the manage page's own controller.

## IP Storage and Search

- `pkg_rdns_ptrs.ip` is `VARBINARY(16)` holding the raw `inet_pton()` form (4 bytes for IPv4, 16 for IPv6). A text `LIKE` against it never matches; any comparison must use binary values.
- `PtrSearch` overrides `findWord()` (same pattern as the parent's `PoolSearch`/`ServerSearch`): complete IPv4/IPv6 addresses in any notation are matched exactly via `inet_pton()`, and partial addresses are matched with `INET6_NTOA(ip) LIKE ...` on MySQL/MariaDB, which renders the same canonical text the UI shows. Other drivers (sqlite in tests) skip the partial branch.
- Always table-qualify columns in search/query code: the client listing joins `entities`, which has its own `ip` column — an unqualified `ip` is ambiguous SQL.
- When comparing or min/maxing binary IP strings in PHP, use `strcmp()` loops — `min()`/`max()`/`==` compare numeric-looking binary strings numerically (e.g. bytes `"1e50"`), breaking byte order. Never use raw binary as a PHP array key (numeric-string casting); prefix it (e.g. `'k' . bin2hex($bin)`).

## Provider Notes (PowerDNS)

- **v3 vs v4 API shapes differ**: v3 (experimental JSON API, path `servers/localhost/...`) uses non-canonical names (no trailing dot), flat `records` in zone creation, and `priority` fields. v4 (path `api/v1/servers/localhost/...`) requires canonical names (trailing dot) and takes **`rrsets`** in zone creation — a 3.x-style flat `records` key is silently ignored and the zone gets the PowerDNS default SOA (`a.misconfigured.powerdns.server.`).
- **Duplicate-zone errors** (hit on every delete, since `deletePtr()` calls `createZone()` first): v3 returns 422 with body `{"error":"Domain 'X' already exists"}` (no trailing dot in X); v4.3 returns 409 with plain-text body `Conflict`. Both are matched exactly in the code.
- **SOA**: content is `<mname> <rname> <serial> <refresh> <retry> <expire> <minimum>`; the first two configured nameservers fill MNAME/RNAME (RNAME being a nameserver is a documented historical quirk — see the `SOA_CONFIG` docblock). Zone creation requires **≥ 2 nameservers** and throws a clear error otherwise.
- `json_decode()` never throws without `JSON_THROW_ON_ERROR` — check for `null`/`isset` when parsing error bodies; don't wrap it in try/catch.

## Adding a New DNS Provider

1. Create a new class in `app/Server/` implementing `IServerControl`.
2. Add a mapping entry in `ServerService::$map`.
3. Add a migration in `database/migrations/` to append the new option to the `pkg.rdns.api.type` setting.
4. If the provider doesn't use certain settings (like `api.host` or `nameservers`), update `admin/app/settings.config.js` to hide those fields when the provider is selected.

## Key Files

- `app/Server/IServerControl.php` — Provider interface
- `app/Server/ServerService.php` — Provider factory (HTTP timeouts, provider-type validation)
- `app/Server/*ServerControl.php` — Provider implementations
- `app/Util/ZoneUtils.php` — Shared utilities for zone/PTR name resolution
- `app/Ptr/` — PTR model, events, listeners, and services
- `app/Ptr/PtrSearch.php` — Search trait handling the binary ip column (see IP Storage and Search)
- `app/Ptr/PtrService.php` — Single create/update plus `createMany()` batch import
- `app/Ptr/Zone/ZoneController.php` — Zone file import parser
- `app/Ptr/Events/` — Loggable events (`PtrCreated`, `PtrDeleted`, `PtrPtrUpdated`). `PtrDeleted` captures ip/ptr as scalars because the model may be gone when queued listeners run.
- `app/Ptr/Listeners/SyncToDnsServer.php` — Queued listener that calls `createPtr()` and appends provider info to the log
- `app/Console/SyncPtrsToProvider.php` — `rdns:sync-to-dns` artisan command to bulk-sync all PTR records to the configured provider
- `admin/app/settings.config.js` — SettingsTab decorator for conditional field visibility
- `database/migrations/` — Schema and settings migrations

## Settings

Provider settings are stored in the `settings` table under the `pkg.rdns.*` namespace:

- `pkg.rdns.api.type` — Provider selection (SynergyCP API, PowerDNS v3, PowerDNS v4, Cloudflare)
- `pkg.rdns.api.host` — Provider host (unused by Cloudflare)
- `pkg.rdns.api.key` — API key / token
- `pkg.rdns.nameservers` — Comma-separated nameservers (unused by Cloudflare; PowerDNS needs ≥ 2)

## Parent App Constraints

- **Do not modify the parent app** (`scp-bm-dev`) for package-specific features.
- The parent app's settings framework only supports showing a group for a single parent option value. To hide fields for specific providers, the package decorates the `SettingsTab` factory in its own Angular module instead.
- Avoid calling `$log->save()` on existing `App\Log\Log` models — model lifecycle events and mutators (e.g., `setUpdatedAtAttribute`) cause errors. Use `DB::table('logs')->where('id', ...)->update(...)` for direct updates. Creating a fresh log via `App\Log\Factory` and saving it once is fine (that's how the listeners' `failed()` handlers log).

## Testing and Verification

- The phpunit suite cannot run from a standalone checkout: `bootstrap/dev.php` expects the package installed inside a SynergyCP app (`../../../bootstrap/autoload.php`), and the parent's vendored phpunit predates PHP 8.4. Verify changes with `php -l` plus checking framework APIs against the parent source at `/scp/scp-bm-dev/api` (Laravel 11).
- Provider payloads can be verified end-to-end against real PowerDNS servers in docker: `psitrax/powerdns:v4.3` + `mariadb:10.3` mirrors the v4 installer stack; PowerDNS 3.4.1 installs in a `debian:jessie` container from `archive.debian.org` with the sqlite backend and `experimental-json-interface=yes`. Customer stacks are defined in `/scp/scp-bm-pkg-rdns-install-powerdns-v3` and `-v4`.
- Zone-import parsing is pure PHP — it can be exercised directly with a scratch script replicating `ZoneController::store()`.

## Commands

- `php artisan test` or `phpunit` — Run tests (requires the package installed inside a parent app; see Testing and Verification)
- `cd admin && gulp` — Build admin frontend assets
- `php artisan rdns:sync-to-dns` — Bulk-sync all existing PTR records to the currently configured DNS provider (useful when migrating between providers)
- Migrations are run by the parent SynergyCP app during package installation

## Releasing Changes

- Bump the `semver` and `release_date_unix` in `scp-package.json` and add a changelog entry describing the change.
- Pushing to `master` triggers the `Build and Deploy Package` GitHub Actions workflow, which builds the frontend, writes package metadata to a Cloudflare D1 database, uploads the tarball to R2, and purges the CDN cache. The D1 step SQL-escapes metadata values (doubled single quotes) — keep it that way; an unescaped apostrophe in a changelog entry once broke the deploy.
- The tarball excludes `.git`, `.github`, `CLAUDE.md`, `node_modules`, and `vendor`; `README.md` ships to customers.

## Admin UI Gotchas

- The SettingsTab decorator in `settings.config.js` replaces `tab.items` to hide/show fields based on the selected provider. This `ng-repeat` re-render can reset Angular form controls to `$pristine`, preventing saves. Any code that modifies `tab.items` inside `onFieldChanged` must re-mark the changed control as `$dirty` via `$setDirty()` afterward.
