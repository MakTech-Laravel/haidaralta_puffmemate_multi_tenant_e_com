---
paths:
  - resources/js/components/platform/product-form.tsx
---

# Platform

## Platform product form layout and suggestions
Platform create/edit ProductForm: details left, media right, grid items-start so gallery growth does not stretch the details card; gallery grid scrolls (max-h). enableSuggestions fetches platform.products.suggestions while typing name (not on branch ProductForm — branch has its own reuse search).

## Product category uses searchable Combobox
Platform ProductForm category field is Popover+Command combobox (searchable), full width, no auto-select of first category on create — only defaults.category_id on edit.
