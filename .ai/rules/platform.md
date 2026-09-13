---
paths:
  - resources/js/components/platform/product-form.tsx
  - resources/js/components/platform/catalog-background-color-field.tsx
---

# Platform

## Platform product form layout and suggestions
Platform create/edit ProductForm: details left, media right, grid items-start so gallery growth does not stretch the details card; gallery grid scrolls (max-h). enableSuggestions fetches platform.products.suggestions while typing name (not on branch ProductForm — branch has its own reuse search).

## Product form multi-selects category_ids
ProductForm categories field is multi-select Popover+Command combobox posting category_ids (number[], min 1). Full width; no auto-select on create — only defaults.category_ids on edit. Keep searchable; toggle chips/checks per option.

## Category multi-select shows name badges
Selected categories render as wrapping secondary Badge chips (keyword style) on the combobox trigger — never a count summary like “N categories selected”.

## Premium catalog color studio UI
CatalogBackgroundColorField is a card studio: large preview canvas, segmented mode cards (None/Solid/Gradient), swatch rows that open a polished popover (hero swatch + hex + palette), gradient bar + angle chips. Keep this premium layout; do not regress to plain Select + tiny swatch.
