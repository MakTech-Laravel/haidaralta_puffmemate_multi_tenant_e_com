# Project structure

File map for the multi-tenant auth system once Phases 1–9 (see
[`multi-tenant-auth-phases.md`](../multi-tenant-auth-phases.md)) have landed. Use this as a guide
for where new, related files should go — keep portal-specific code namespaced by portal
(`Platform/`, `Branch/`, `Driver/`, `Customer/`) on both backend and frontend.

## Backend

```
app/
├── Enums/
│   ├── UserType.php                 # platform | branch | driver | customer
│   ├── RoleEnum.php                 # super-admin | admin | manager | staff
│   └── PermissionEnum.php           # all seeded permission strings, guard()/group()/forGuard()
├── Support/
│   └── PlatformTeamResolver.php     # reserves Spatie team id 0 for platform scope
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   ├── ForgotPasswordController.php
│   │   │   └── ResetPasswordController.php
│   │   ├── Platform/
│   │   │   ├── DashboardController.php
│   │   │   ├── RoleController.php           # Phase 8
│   │   │   ├── PermissionController.php     # Phase 8 (read-only catalog)
│   │   │   └── BranchStaffController.php    # Phase 8 (cross-branch oversight)
│   │   ├── Branch/
│   │   │   ├── DashboardController.php
│   │   │   └── StaffController.php          # Phase 8 (own-branch only)
│   │   ├── Driver/
│   │   │   └── DashboardController.php
│   │   └── Customer/
│   │       └── DashboardController.php
│   ├── Middleware/
│   │   ├── EnsurePortalGuard.php     # alias: ensure.guard
│   │   ├── ResolveBranchContext.php  # alias: resolve.branch
│   │   └── HandleInertiaRequests.php # shares auth.user.{roles,permissions,is_super_admin}, portal, branch
│   └── Requests/
│       ├── Auth/LoginRequest.php
│       ├── Platform/StoreRoleRequest.php, UpdateRoleRequest.php       # Phase 8
│       └── Branch/StoreBranchStaffRequest.php, UpdateBranchStaffRequest.php  # Phase 8
├── Models/
│   ├── User.php           # HasRoles trait, UserType cast, branch(), isPlatform()/isBranch()/...
│   └── Branch.php
└── Providers/
    └── AuthServiceProvider.php   # (or bootstrap/app.php) Gate::before super-admin bypass

database/
├── migrations/
│   ├── ..._create_permission_tables.php   # Spatie stock schema (teams-enabled, NOT patched)
│   ├── ..._create_branches_table.php
│   └── ..._add_portal_fields_to_users_table.php   # user_type, branch_id, is_active
├── seeders/
│   ├── RolesAndPermissionsSeeder.php   # platform roles/perms + seedBranchRolesForBranch()
│   ├── BranchSeeder.php                # sample branches, triggers branch role seeding
│   ├── UserSeeder.php                  # one test user per user_type
│   └── DatabaseSeeder.php
└── factories/
    └── UserFactory.php

config/
├── auth.php          # 5 guards: web, platform, branch, driver, customer
└── permission.php     # teams => true, team_foreign_key => branch_id, team_resolver => PlatformTeamResolver

routes/
├── web.php            # 4 guard-scoped route groups + shared /login, /forgot-password, /reset-password
└── auth.php           # optional split-out, if the starter kit's convention prefers it

tests/
└── Feature/
    ├── Auth/LoginTest.php
    ├── Auth/PasswordResetTest.php
    ├── Auth/LogoutTest.php
    └── Isolation/CrossPortalAccessTest.php   # the most important file — cross-guard/cross-branch assertions
```

## Frontend

```
resources/js/
├── Pages/
│   ├── Auth/
│   │   ├── Login.tsx
│   │   ├── ForgotPassword.tsx
│   │   └── ResetPassword.tsx
│   ├── Platform/
│   │   ├── Dashboard.tsx
│   │   ├── Roles/{Index,Create,Edit,Show}.tsx        # Phase 8
│   │   ├── Permissions/Index.tsx                     # Phase 8
│   │   └── BranchStaff/{Index,Show}.tsx               # Phase 8
│   ├── Branch/
│   │   ├── Dashboard.tsx
│   │   └── Staff/{Index,Create,Edit}.tsx              # Phase 8
│   ├── Driver/
│   │   └── Dashboard.tsx
│   └── Customer/
│       └── Dashboard.tsx
├── Layouts/
│   ├── PlatformLayout.tsx
│   ├── BranchLayout.tsx
│   ├── DriverLayout.tsx
│   └── CustomerLayout.tsx
├── components/
│   ├── navigation/
│   │   ├── types.ts        # NavNode
│   │   ├── nav-utils.ts    # filterNavNodes()
│   │   └── sidebar-nav.tsx
│   ├── platform/nav.ts
│   ├── branch/nav.ts
│   ├── driver/nav.ts
│   ├── customer/nav.ts
│   ├── permission-selector.tsx   # Phase 8, shared by role create/edit forms
│   ├── file-upload.tsx           # Full uploader + lightbox
│   ├── file-upload-avatar.tsx    # Avatar wrapper
│   ├── error-boundary/           # react-error-boundary (dev overlay + prod)
│   ├── icons/                    # Full Lucide picker (dialog/sheet/collapsible)
│   └── navigation/               # Nested SidebarNav, badges, flyouts
├── lib/
│   ├── icon-catalog.ts           # Phase 9 (generated)
│   └── lucide-icon-cache.ts      # Phase 9 lazy Lucide loader
├── hooks/
│   ├── use-permissions.tsx   # can/canAny/canAll/hasRole/hasAnyRole
│   └── use-branch.ts         # branch context, throws outside the branch portal
└── types/
    ├── permissions.ts   # PermissionKey union mirroring PermissionEnum
    └── tenant.ts         # BranchContext interface

docs/                      # you are here
├── README.md
├── permissions.md
├── middleware-and-tenancy.md
├── layouts-and-dashboards.md
├── permission-checks-in-ui.md
├── shared-infrastructure.md      # Phase 9
└── project-structure.md

multi-tenant-auth-phases.md   # phase-by-phase build plan + master prompts (project root)
```

## Shared components (Phase 9)

See [shared-infrastructure.md](shared-infrastructure.md) for error boundary, icon picker,
file upload, and Telescope setup. These are generic UI/dev tools — they do not alter the
auth or isolation boundary.
