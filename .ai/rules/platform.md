---
paths:
  - resources/js/components/platform/product-form.tsx
---

# Platform

## Platform product form layout and suggestions
Platform create/edit ProductForm: details left, media right, grid items-start so gallery growth does not stretch the details card; gallery grid scrolls (max-h). enableSuggestions fetches platform.products.suggestions while typing name (not on branch ProductForm — branch has its own reuse search).

## Product form multi-selects category_ids
ProductForm categories field is multi-select Popover+Command combobox posting category_ids (number[], min 1). Full width; no auto-select on create — only defaults.category_ids on edit. Keep searchable; toggle chips/checks per option.
