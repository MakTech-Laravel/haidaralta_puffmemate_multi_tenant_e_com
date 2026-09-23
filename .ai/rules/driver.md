---
paths:
  - 'app/Services/Driver/**'
  - 'app/Models/Driver*.php'
  - 'app/Http/Controllers/{Platform,Branch}/*Driver*.php'
  - 'resources/js/pages/{platform,branch}/drivers/**'
  - 'resources/js/pages/platform/branches/drivers/**'
  - 'app/Http/Controllers/{Platform,Branch}/**'
---

# Driver

## driver_id means users.id

driver_id columns always reference users.id (user_type=driver, branch_id null). driver_profiles.user_id is the 1:1 extension only. Never introduce driver_profile_id on assignments, rosters, or future delivery/location tables. Assignment count is `config('drivers.single_active_branch')`: true transfers (one active); false allows multiple actives. Unique remains `(driver_id, branch_id)` only — never a unique-on-active index.

## Driver CRUD surfaces

Platform /platform/drivers is the full driver admin (create requires ≥1 branch_ids; max:1 only when `drivers.single_active_branch` is true). Nested platform.branches.drivers and branch.drivers auto-assign the current branch on create and can assign existing drivers. Duty shifts are not nested on create/edit — use Branch management Assign to shift. No parcel/GPS/chat yet.

## Driver UX: Sheet assigner, media, effective availability

Platform driver assign UI is DriverBranchAssignSheet (right Sheet + server search/pagination via platform.drivers.branch-options). Selection is exclusive when `drivers.single_active_branch` is true, multi-checkbox when false. Copy/actions follow the same flag. driver_profiles stores address/vehicle_image_path (MediaUrl). User avatars live on users.avatar for every user type. List/detail show effective_availability: on_break/busy overrides win, else scheduled shift day covering now → available, else offline.

## Nested platform staff/driver indexes redirect to filtered lists

Platform nested staff and drivers INDEX actions redirect to platform.branch-staff.index and platform.drivers.index with filter[branch_id] for that branch (preserving other filter[] query keys). Nested create/show/edit may remain. Do not restore platform/branches/staff/index or platform/branches/drivers/index Inertia pages. Branch portal lists stay on branch.staff / branch.drivers.

## Gate one-active-branch on config

Assignment mode is `config('drivers.single_active_branch')` (env `DRIVER_SINGLE_ACTIVE_BRANCH`, default true), not an unconditional one-branch law. True: Active assign/sync/status transfers (deactivates other actives) and rejects more than one branch_id. False: multiple actives are allowed. Unique remains `(driver_id, branch_id)` only — never a unique-on-active index.

## Platform shift workspaces use separate controllers
Administrator Shift* controllers handle owner_type=platform and branch_id null only. Branch management uses BranchShift* controllers for owner_type=branch with required branch_id. Do not point two route URIs at the same controller method (Wayfinder becomes a URI dictionary). Cross-owner edit/assign is 404. Branch portal Shift* controllers stay branch-scoped and may still assign onto visible platform templates.

## Drivers assign shifts via Branch management
Driver create/edit has no Duty shifts UI or nested roster payloads. Admin driver show pages omit Current duty / Duty windows. Shifts are managed under Branch management. Drivers list Assign to shift links to branch-shift-assignments.create (platform) or branch.shift-assignments.create (portal) with people_kind=driver, user_id, and branch_id (platform: first listed branch).
