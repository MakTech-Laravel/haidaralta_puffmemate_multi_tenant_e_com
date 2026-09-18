---
paths:
  - app/Http/Controllers/Platform/AdminController.php
  - app/Http/Controllers/Platform/BranchStaffController.php
---

# Controllers Platform

## Platform users are Admins at /platform/admins
Platform Administrator CRUD lives at /platform/admins (platform.admins), not /platform/staff. Old staff URLs 301. Branch staff and /platform/branch-staff stay named Staff.

## Nested branch staff index redirects to Staff
Platform nested staff index (platform.branches.staff.index) 302s to /platform/branch-staff?filter[branch_id]=. Do not restore the nested staff list page. Create/show/edit nested routes may remain.
