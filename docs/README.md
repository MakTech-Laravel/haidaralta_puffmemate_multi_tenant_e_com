# Documentation

Developer guides for this multi-tenant e-commerce platform's auth system. Everything here
describes **the design as planned in [`multi-tenant-auth-phases.md`](../multi-tenant-auth-phases.md)**,
adapted from patterns proven in a reference multi-tenant project (`blake_lms_tenant`). Update
these guides as each phase actually lands in code, so they stay accurate rather than aspirational.

## Architecture at a glance

Four portals share one Laravel app, separated by dedicated auth guards and (for branch staff) a
Spatie permission team:

| Portal | URL prefix | Guard | Who | Access model |
|---|---|---|---|---|
| **Platform** | `/platform/*` | `platform` | platform owner/staff | roles in the reserved platform team (`0`) |
| **Branch** | `/branch/{branch}/*` | `branch` | branch staff | roles team-scoped to their branch |
| **Driver** | `/driver/*` | `driver` | delivery drivers | no roles — guard + ownership checks only |
| **Customer** | `/customer/*` | `customer` | shoppers | no roles — guard + ownership checks only |

Authorization is [`spatie/laravel-permission`](https://spatie.be/docs/laravel-permission) with
**Teams** (the "team" is a branch), and a single team-aware `Gate::before` super-admin bypass.
Unlike a deeper platform-school-branch hierarchy, **this project has only one tenancy boundary**
(platform vs branch) — there is no third nested scope. Driver and customer guards exist purely for
authentication isolation; they carry no Spatie roles at all.

## Why a reserved team id (`0`) instead of `null`

PostgreSQL does not treat `NULL = NULL` as equal in a unique constraint, so a nullable
`branch_id` on the roles table cannot stop two identically-named platform roles from being
created. This project reserves team id `0` for the platform scope (via `PlatformTeamResolver`)
instead, keeping Spatie's original composite-primary-key migration schema untouched. See
[permissions.md](permissions.md) for the full rationale and how to work with it.

## Guides

- **[permissions.md](permissions.md)** — the team-scoping model (reserved id `0`), the
  `UserType`/`RoleEnum`/`PermissionEnum` enums, adding a new permission step-by-step, and the
  super-admin bypass (`Gate::before` + team-aware `hasRole()`).
- **[middleware-and-tenancy.md](middleware-and-tenancy.md)** — `EnsurePortalGuard`,
  `ResolveBranchContext`, registration/order in `bootstrap/app.php`, and how to add a new
  protected route per portal.
- **[layouts-and-dashboards.md](layouts-and-dashboards.md)** — the layout resolver pattern, where
  each portal's layout + sidebar live, permission-based nav filtering, and how to add a new
  dashboard page.
- **[permission-checks-in-ui.md](permission-checks-in-ui.md)** — `usePermission()`: `can` /
  `canAny` / `canAll` / `hasRole` / `hasAnyRole`, with real component examples.
- **[project-structure.md](project-structure.md)** — backend + frontend file map and where new
  files go.

For the phase-by-phase build plan itself (including exact master prompts to run in Cursor for
each phase), see [`multi-tenant-auth-phases.md`](../multi-tenant-auth-phases.md) at the project
root.
