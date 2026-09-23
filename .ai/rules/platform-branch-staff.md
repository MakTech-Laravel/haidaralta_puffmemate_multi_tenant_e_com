---
paths:
  - 'resources/js/components/admin/staff-*.tsx,resources/js/pages/platform/branch-staff/**'
---

# Platform Branch Staff

## Staff branch picker is infinite combobox
Platform staff create uses InfiniteScrollCombobox against platform.branch-staff.branch-options (20/page, filter[search]); do not dump branches or rolesByBranch into create props. No default branch. Roles load from platform.branch-staff.role-options?branch_id= after pick. Required labels use primary *.
