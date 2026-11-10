---
paths:
    - resources/js/pages/platform/branches/index.tsx
    - resources/js/pages/platform/branches/show.tsx
---

# Branches

## Platform branch list actions are grouped

Platform Branches list row menus use labeled DataTableRowActions groups, not a flat items list. Branch: View / Edit. People: Staff / Drivers. Stock: Products, Manage stock (stock/manage?branch=), Stock transactions, Price history. Danger zone: Delete. Reports use the same branch query. Do not add Add stock, Update price, or Add discount.

## Branch list Staff/Drivers use global filtered lists

People row actions Staff and Drivers go to the global lists with Spatie filter[branch_id]: /platform/branch-staff and /platform/drivers. Do not send them to nested /platform/branches/{slug}/staff or /drivers list pages. Nested platform indexes 302 to those filtered URLs. Branch portal staff/drivers lists stay nested.

## Platform branch show is compact plus staff DataTable

Platform branch show is a compact profile (identity, fact grid, StatCards including Areas count) plus a Service areas card (this branch’s areas only — name, place, status, link to branch-areas show) plus BranchStaffDataTable::forBranch. Full area CRUD stays on the filtered global areas list (`filteredBranchAreasUrl`). Do not add a second areas DataTable on show. Do not restore the dashed map placeholder or an unpaginated super_admins list. Staff search/role/status/pagination uses Spatie filter[]/sort/per_page/page on the show URL. needs_super_admin still comes from hasSuperAdmin(). No inline live map — coords are facts; Mapbox/Google stays in the Edit dialog.

## Branch show View on map opens a dialog

When a branch has coordinates, show View on map (details header and the Location tile). It opens BranchLocationMapDialog: OpenStreetMap embed in the dialog, plus Google Maps / OpenStreetMap links. Do not restore an inline dashed map placeholder. Hide the action when there is no pin.
