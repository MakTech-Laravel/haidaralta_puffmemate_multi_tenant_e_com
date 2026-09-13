---
paths:
  - 'resources/js/components/branch/**'
  - resources/js/components/branch/nav.ts
---

# Components Branch

## Branch inventory panel scopes by variant
Branch/platform product show uses ProductInventoryPanel: base + variant scope selector, posts existing inventory/price routes with variant_id. Never edit global catalog metadata from branch. UI uses Card sections + shared `DatePicker` (`@/components/ui/date-picker`, Calendar popover, `yyyy-MM-dd`) — prefer over native `input type="date"`.

## Branch Catalog nav children
Branch Catalog sidebar group (manage-branch-products) lists Categories + Products. Categories routes have no destroy/restore.
