---
paths:
  - resources/js/pages/platform/branches/index.tsx
---

# Branches

## Platform branch list actions are grouped
Platform Branches list row menus use labeled DataTableRowActions groups, not a flat items list. Branch: View / Edit. People: Staff / Drivers. Stock: Products, Manage stock (stock/manage?branch=&locked=branch), Stock transactions, Price history. Danger zone: Delete. Reports use the same branch query with locked omitted. Do not add Add stock, Update price, or Add discount.

## Branch list Staff/Drivers use global filtered lists
People row actions Staff and Drivers go to the global lists with Spatie filter[branch_id]: /platform/branch-staff and /platform/drivers. Do not send them to nested /platform/branches/{slug}/staff or /drivers list pages. Nested platform indexes 302 to those filtered URLs. Branch portal staff/drivers lists stay nested.
