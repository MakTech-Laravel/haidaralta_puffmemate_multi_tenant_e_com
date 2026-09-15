---
paths:
  - 'app/Services/Driver/**'
  - 'app/Models/Driver*.php'
  - 'app/Http/Controllers/{Platform,Branch}/*Driver*.php'
  - 'resources/js/pages/{platform,branch}/drivers/**'
  - 'resources/js/pages/platform/branches/drivers/**'
---

# Driver

## driver_id means users.id
driver_id columns always reference users.id (user_type=driver, branch_id null). driver_profiles.user_id is the 1:1 extension only. Never introduce driver_profile_id on assignments, rosters, or future delivery/location tables. Multi-branch via driver_branch_assignments.

## Driver CRUD surfaces
Platform /platform/drivers is the full driver admin (create requires ≥1 branch_ids). Nested platform.branches.drivers and branch.drivers auto-assign the current branch on create and can assign existing drivers. Rosters require an active assignment for that branch; overlapping scheduled shifts for the same driver_id+date are rejected. No parcel/GPS/chat yet.
