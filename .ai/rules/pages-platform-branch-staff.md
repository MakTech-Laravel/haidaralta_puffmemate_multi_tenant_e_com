---
paths:
  - 'resources/js/pages/platform/branch-staff/**'
---

# Pages Platform Branch Staff

## Staff list branch filter is infinite combobox
Platform branch-staff index Branch filter uses InfiniteScrollCombobox against platform.branch-staff.branch-options (noneLabel All branches). BranchStaffDataTable acrossBranches filterOptions carry selected_branch only—never dump filterOptions.branches.
