---
paths:
  - resources/js/components/platform/product-form.tsx
  - resources/js/components/platform/category-form.tsx
---

# Platform

## Platform product form layout and suggestions
Platform create/edit ProductForm: details left, media right, grid items-start (no sticky Media scroll). Media top row: ColorPicker + primary FileUpload side-by-side with items-stretch (equal card height); primary is full-width rectangular drop/preview on color tile (not avatar). Gallery grid max-h scroll. Over-limit gallery picks collapse to one count rejection message. enableSuggestions fetches platform.products.suggestions while typing name (not on branch ProductForm — branch has its own reuse search).

## Product form multi-selects category_ids
ProductForm categories field is multi-select Popover+Command combobox posting category_ids (number[], min 1). Full width; no auto-select on create — only defaults.category_ids on edit. Keep searchable; toggle chips/checks per option.

## Category multi-select shows name badges
Selected categories render as wrapping secondary Badge chips (keyword style) on the combobox trigger — never a count summary like “N categories selected”.

## Catalog color_code uses ColorPicker dialog
Product/category forms use ColorPicker mode=dialog with enabledModes transparent|solid|gradient and defaultMode=transparent. Post JSON.stringify(color_code). Display tiles via colorPickerStyle. Do not enable radial until PHP CatalogBackgroundMode supports it.

## Product primary and gallery image types
ProductForm primary + gallery accept PNG, JPG/JPEG, SVG, WebP (max 2MB). Backend ValidatesCatalogProductImages uses image:allow_svg + mimes; serve SVG as img src only.
