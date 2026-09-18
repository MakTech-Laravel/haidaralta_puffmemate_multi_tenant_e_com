# Shared infrastructure

Reusable UI and local-dev tooling (Phase 9+). None of this changes portal guards,
Spatie team scoping, or isolation middleware.

## Error boundary

**Location:** `resources/js/components/error-boundary/`

Uses `react-error-boundary` around the Inertia tree in `resources/js/app.tsx` (`withApp`).

| Piece                           | Role                                               |
| ------------------------------- | -------------------------------------------------- |
| `error-boundary-fallback.tsx`   | Chooses DEV vs PROD UI via `useDevErrorFallback()` |
| `error-dev-fallback.tsx`        | Overlay → dismiss → floating badge → reopen        |
| `error-overlay.tsx`             | Stack / component stack / history tabs             |
| `error-badge.tsx`               | Floating badge when overlay is dismissed           |
| `error-store.ts`                | Persists recent errors in `sessionStorage`         |
| `error-production-fallback.tsx` | Friendly Reload / Back / Home card                 |

Set `VITE_ERROR_BOUNDARY_DEBUG=true|false` to force the UI mode; unset follows `import.meta.env.DEV`.

## Icon picker

**Location:** `resources/js/components/icons/` (full Lucide picker), `resources/js/lib/icons.tsx`, `icon-catalog.ts`, `lucide-icon-cache.ts`

Icons load **lazily** through `lucide-react/dynamicIconImports` — never static-import the full Lucide set.

| Export                 | Use                                                 |
| ---------------------- | --------------------------------------------------- |
| `LucideIconPicker`     | Dialog / sheet / collapsible picker                 |
| `CachedLucideIcon`     | Render a catalog key without re-fetching icon nodes |
| `Icon` (`@/lib/icons`) | Render by kebab-case name                           |

Regenerate the catalog after upgrading `lucide-react`:

```bash
npm run generate:icons
```

## File upload

**Location:** `resources/js/components/file-upload.tsx`, `file-upload-avatar.tsx`

Full blake-style uploader:

- Single / multiple, drag-and-drop, click browse
- Image / video / document previews + existing server files
- Per-file or aggregate progress, cancel, rejections via `onReject`
- Optional lightbox (`yet-another-react-lightbox`, off by default)
- `variant="avatar"` for circular single-image pickers
- Imperative `ref`: `openFileBrowser()` / `clearFiles()`

Backend `Storage` endpoints, S3, and Inertia file posting belong to future business features.

## Navigation

**Location:** `resources/js/components/navigation/`

Permission-aware nestable sidebar nav (`SidebarNav` + `NavNodeItem`), with badges, flyouts when the sidebar is icon-collapsed, and animated expand/collapse (`motion`).

Portal shells pass items through `PortalShellLayout` → `<SidebarNav items={…} label={…} />`.

## Telescope

**Package:** `laravel/telescope` (dev dependency)

| Concern           | Behavior                                                                                                                                                             |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Package discovery | `laravel/telescope` is listed under `dont-discover` in `composer.json` so it only loads via our provider                                                             |
| Registration      | `TelescopeServiceProvider` only when `APP_ENV` is `local` or `staging` **and** `TELESCOPE_ENABLED` is true ([`bootstrap/providers.php`](../bootstrap/providers.php)) |
| Authorization     | Platform users with `super-admin` only — see `App\Support\TelescopeAccess`                                                                                           |
| Testing           | `phpunit.xml` sets `TELESCOPE_ENABLED=false` and `APP_ENV=testing`; `/telescope` returns 404                                                                         |
| Production        | Provider not registered; do **not** enable without an explicit security review                                                                                       |

Set `TELESCOPE_ENABLED=true` in your local `.env` (see `.env.example`). If it is `false`, Telescope routes will not register even in `local`.

## Horizon and Pulse

**Packages:** `laravel/horizon`, `laravel/pulse` (Livewire is a Pulse dependency)

| Concern       | Behavior                                                                                                                                                      |
| ------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Process model | Same Coolify app container: Horizon replaces `queue:work`; `pulse:check` runs under Supervisor; schedule runs `horizon:snapshot`                              |
| Dashboards    | Official UIs at `/horizon` (Vue) and `/pulse` (Blade/Livewire). Platform Inertia hub at `/platform/observability`                                             |
| Authorization | Platform only — `view-horizon` / `view-pulse`, or platform `super-admin` bypass (`App\Support\OpsDashboardAccess`)                                            |
| Testing       | `phpunit.xml` keeps `PULSE_ENABLED=false`; feature coverage in `OpsDashboardAccessTest`                                                                       |
| Local Windows | Keep `QUEUE_CONNECTION=database`; Horizon needs Redis + pcntl (Linux image / Coolify)                                                                         |
| Cache         | Laravel 13 `cache.serializable_classes` must allow-list `Collection`, `stdClass`, and `CarbonImmutable` or Pulse Livewire polls fatal with incomplete objects |
