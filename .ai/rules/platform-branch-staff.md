---
paths:
  - 'resources/js/components/admin/staff-*.tsx,resources/js/pages/platform/branch-staff/**'
  - 'resources/js/components/admin/transfer-employee-sheet.tsx,resources/js/pages/platform/branch-staff/**'
---

# Platform Branch Staff

## Staff branch picker is infinite combobox
Platform staff create, edit (unlocked), and the staff list Branch filter use InfiniteScrollCombobox against platform.branch-staff.branch-options (20/page, filter[search]); do not dump branches or rolesByBranch into props. Create: no default branch; roles load from role-options?branch_id= after pick; required labels use primary *. List: noneLabel All branches; DataTable filterOptions.selected_branch only. Nested/branch-portal edit keeps branchLocked.

## Platform staff transfer sheet and edit-as-transfer
Platform staff Transfer (list + show) uses TransferEmployeeSheet with InfiniteScrollCombobox branch-options and role-options (required role, current branch read-only). Never dump branches/rolesByBranch on index/show. Changing branch_id on platform staff edit calls TransferEmployeeService with reason Updated via staff edit. Active branches (status=true) must keep a super-admin; inactive branches may transfer the last SA away.
