# Access admin (Platform + Branch RBAC)

Admin surfaces for roles, permissions, and staff under the platform and branch portals.

## Guards and teams

| Portal   | Guard      | Spatie team id                                 |
| -------- | ---------- | ---------------------------------------------- |
| Platform | `platform` | `0` (`PlatformTeamResolver::PLATFORM_TEAM_ID`) |
| Branch   | `branch`   | `branches.id` (set by `resolve.branch`)        |

Roles and permissions are always team-scoped. Platform staff users have `users.branch_id = null`; branch staff are pinned to a branch.

## Permissions

| Permission            | Guard    | What it unlocks                                          |
| --------------------- | -------- | -------------------------------------------------------- |
| `manage-roles`        | platform | Platform roles CRUD, permission catalog, CSV export      |
| `manage-users`        | platform | Platform staff CRUD, branch-staff overview               |
| `manage-branch-roles` | branch   | Branch roles CRUD, branch permission catalog, CSV export |
| `manage-branch-staff` | branch   | Branch staff CRUD                                        |

Branch managers are seeded **without** `manage-branch-staff` and `manage-branch-roles`.

## Routes

### Platform (`ensure.guard:platform`)

- `platform/roles` (+ `roles/export`)
- `platform/permissions` (+ `permissions/export`)
- `platform/staff`
- `platform/branch-staff` (read-only oversight)

### Branch (`ensure.guard:branch` + `resolve.branch`)

- `branch/{branch}/roles` (+ export)
- `branch/{branch}/permissions` (+ export)
- `branch/{branch}/staff`

## UX conventions

- Success/error feedback uses `App\Support\Toast` → Inertia flash `toast` → Sonner.
- Super-admin role cannot be renamed/deleted; assigned roles cannot be deleted.
- Last active super-admin in a team cannot be deactivated/deleted or stripped of the role (`App\Support\SuperAdmin`).
- Shared UI: `RoleForm`, `StaffForm`, `PermissionSelector`, `ConfirmDeleteDialog`.

## Related docs

- [permissions.md](permissions.md) — team model and enums
- [permission-checks-in-ui.md](permission-checks-in-ui.md) — `usePermission()` / nav gating
