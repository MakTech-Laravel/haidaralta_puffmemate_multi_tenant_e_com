---
paths:
  - resources/js/pages/platform/branches/index.tsx
---

# Branches

## Platform branch list actions are grouped
Platform Branches list row menus use labeled DataTableRowActions groups, not a flat items list. Branch: View / Edit. People: Staff / Drivers. Stock: Products, Manage stock (stock/manage?branch=&locked=branch), Stock transactions, Price history. Danger zone: Delete. Reports use the same branch query with locked omitted. Do not add Add stock, Update price, or Add discount.
