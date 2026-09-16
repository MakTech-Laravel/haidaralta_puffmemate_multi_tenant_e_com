---
paths:
  - 'resources/js/components/{ui/date-picker,branch/product-inventory-panel}.tsx'
---

# Date Pickerbranch

## Inventory panel uses DatePicker cards
Branch/platform inventory manage UI uses Card sections + shared DatePicker (Calendar popover, yyyy-MM-dd). Prefer DatePicker over native input type=date.

## DatePicker clear stays inside the field
Clear (X) stays inside the DatePicker trigger (absolute, left of the calendar icon). Do not place it as a sibling of a w-full trigger — that overflows into the next grid cell. Root is relative w-full min-w-0; trigger uses shrink overflow-hidden and extra pr-16 when clear is shown.
