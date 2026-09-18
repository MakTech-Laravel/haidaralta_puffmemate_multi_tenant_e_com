---
paths:
  - resources/js/pages/platform/branches/index.tsx
  - resources/js/pages/platform/branches/show.tsx
---

# Branches

## Platform branch list actions are grouped
Platform Branches list row menus use labeled DataTableRowActions groups, not a flat items list. Branch: View / Edit. People: Staff / Drivers. Stock: Products, Manage stock (stock/manage?branch=&locked=branch), Stock transactions, Price history. Danger zone: Delete. Reports use the same branch query with locked omitted. Do not add Add stock, Update price, or Add discount.

## Branch list Staff/Drivers use global filtered lists
People row actions Staff and Drivers go to the global lists with Spatie filter[branch_id]: /platform/branch-staff and /platform/drivers. Do not send them to nested /platform/branches/{slug}/staff or /drivers list pages. Nested platform indexes 302 to those filtered URLs. Branch portal staff/drivers lists stay nested.

## Platform branch show is compact plus staff DataTable
Platform branch show is a compact profile (identity, fact grid, StatCards) plus BranchStaffDataTable::forBranch. Do not restore the dashed map placeholder or an unpaginated super_admins list. Staff search/role/status/pagination uses Spatie filter[]/sort/per_page/page on the show URL. needs_super_admin still comes from hasSuperAdmin(). No inline live map — coords are facts; Mapbox/Google stays in the Edit dialog.
