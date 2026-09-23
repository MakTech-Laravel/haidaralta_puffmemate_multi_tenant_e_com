---
paths:
  - app/Http/Controllers/Platform/AdminController.php
  - app/Http/Controllers/Platform/BranchStaffController.php
  - 'app/Http/Controllers/Platform/*Shift*.php'
---

# Controllers Platform

## Platform users are Admins at /platform/admins

Platform Administrator CRUD lives at /platform/admins (platform.admins), not /platform/staff. Old staff URLs 301. Branch staff and /platform/branch-staff stay named Staff.

## Nested branch staff index redirects to Staff

Platform nested staff index (platform.branches.staff.index) 302s to /platform/branch-staff?filter[branch_id]=. Do not restore the nested staff list page. Create/show/edit nested routes may remain.

## Platform Shift controllers are owner-scoped
Administrator Shift* controllers are platform-owned only (owner_type=platform, branch_id null). Branch management uses BranchShift* controllers for branch-owned records. Separate classes are required so Wayfinder stays callable. Cross-owner edit or assign is 404.

## Branch shifts load one store
Branch-owned shift pages never dump every store. Always scope templates, on-duty, assigned people, and assign lists to a single `?branch_id=` (integer, not Spatie filter). With no branch_id, the index is empty until the operator picks a store. There is no All-branches option and no auto-selected default. Redirects after create/update must keep that branch_id. Platform-owned shifts stay unscoped.

## Do not dump every branch into shift pages
Branch shift combobox loads GET platform.branch-shift-templates.branch-options (20/page, filter[search]). Index/create/assign/assigned-people pass only selectedBranch, never the full branch list. Create without `?branch_id=` redirects to the empty Branch shifts index. Assign and Assigned people without `?branch_id=` stay on their URLs and show the choose-a-branch empty state. There is no New period route.
