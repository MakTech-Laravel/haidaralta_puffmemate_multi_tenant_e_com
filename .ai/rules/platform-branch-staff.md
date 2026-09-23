---
paths:
  - 'resources/js/components/admin/staff-*.tsx,resources/js/pages/platform/branch-staff/**'
---

# Platform Branch Staff

## Staff branch picker is infinite combobox
Platform staff create and the staff list Branch filter use InfiniteScrollCombobox against platform.branch-staff.branch-options (20/page, filter[search]); do not dump branches or rolesByBranch into props. Create: no default branch; roles load from role-options?branch_id= after pick; required labels use primary *. List: noneLabel All branches; DataTable filterOptions.selected_branch only.
