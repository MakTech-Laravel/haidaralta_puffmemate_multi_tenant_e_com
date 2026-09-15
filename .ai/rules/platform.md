---
paths:
  - resources/js/components/platform/product-form.tsx
  - resources/js/components/platform/category-form.tsx
  - 'resources/js/components/platform/{category,product}-form.tsx'
---

# Platform

## Platform product form layout and suggestions
Platform create/edit ProductForm uses a media rail, not equal columns: `xl:grid-cols-[minmax(0,1fr)_minmax(17.5rem,20rem)]`, items-start, no sticky Media. DOM order is details → media (`xl:row-span-2`) → merchandising so fields sit under details instead of leaving a hole beside a tall gallery. Compact square primary (no min-h-96, no filename bar) + ColorPicker triggerVariant=button. Gallery uses FileUpload dropzoneVisibility=empty (dashed zone only when empty; add tile when filled) and compactCards 3-col thumbs. Tags + specifications stay in Storefront merchandising under details. Slug/SKU share a 2-col row. Over-limit gallery picks collapse to one count rejection. enableSuggestions fetches suggestionsUrl while typing name.

## Catalog slug unique via Precognition
CategoryForm and ProductForm live-validate `slug` uniqueness with Inertia useForm Precognition (blur on name after auto-slug sync, and blur on slug). Store/update routes must keep `precognitive` middleware. validationTimeout 500.

## Category form name suggestions
CategoryForm enableSuggestions + suggestionsUrl + suggestionShowHref show similar categories while typing name (platform + branch create/edit). Exclude current id on edit.
## Product form multi-selects category_ids
ProductForm categories field is multi-select Popover+Command combobox posting category_ids (number[], min 1). Full width; no auto-select on create — only defaults.category_ids on edit. Keep searchable; toggle chips/checks per option.

## Category multi-select shows name badges
Selected categories render as wrapping secondary Badge chips (keyword style) on the combobox trigger — never a count summary like “N categories selected”.

## Catalog color_code uses ColorPicker dialog
Product/category forms use ColorPicker mode=dialog with enabledModes transparent|solid|gradient. Product create defaultMode=solid with value solid #EF4444; category create stays defaultMode=transparent. Post JSON.stringify(color_code). Display tiles via colorPickerStyle. Do not enable radial until PHP CatalogBackgroundMode supports it.

## Product primary and gallery image types
ProductForm primary + gallery accept PNG, JPG/JPEG, SVG, WebP (max 2MB). Backend ValidatesCatalogProductImages uses image:allow_svg + mimes; serve SVG as img src only.

## Category form matches product media layout
Platform CategoryForm mirrors ProductForm: details left / listing preview right, items-start (no sticky Media scroll). Both use a listing-preview card (image on the color tile + compact ColorPicker in the footer), not a side-by-side field ColorPicker. Create/edit pages use full width p-2 md:p-4. Image accept PNG/JPG/SVG/WebP via ValidatesCatalogProductImages.

## Category media is a listing preview card
CategoryForm Media is one listing-preview card: full-width image upload on the color tile, compact ColorPicker triggerVariant=button in a footer row. Do not side-by-side stretch field ColorPicker with FileUpload (leaves empty space without a gallery). Equal-height xl:grid-cols-2 details | preview.

## Parent category uses searchable combobox
CategoryForm parent_id is a single-select Popover+Command combobox (searchable), with a No parent option that clears parent_id. Match ProductForm combobox styling; close on select.

## Category preview image is centered
Category listing-preview FileUpload card is max-w centered (mx-auto) with square object-contain image. Single-file FileUpload must not force an h-full inner wrapper or vertical centering fails.

## Center category image, no nested card
Category listing preview: do not shrink FileUpload into a nested max-w card. Keep full-width transparent shell; center the image via cardImage object-contain (mx-auto max-h). FileUpload supports classNames.cardImage.

## Branch ProductForm ops fields keep gallery
Branch create/edit use ProductForm with gallery enabled (same as platform). branchOpsFields adds selling_price/quantity/minimum_stock on create only. createFromBranch / update persist gallery via ProductService.

## Catalog forms suggestions + slug Precognition
CategoryForm and ProductForm: enableSuggestions with portal-specific suggestionsUrl; slug uniqueness via Precognition on name/slug blur. Routes need precognitive middleware.

## Product form listing preview and solid red default
ProductForm create defaults color_code to solid #EF4444 with defaultMode=solid. Edit keeps the stored value (null → transparent). Media is a narrow rail (not a second equal column): square primary on the color tile, compact ColorPicker in the footer, gallery thumbs with dropzoneVisibility=empty. Do not use min-h-96 or a persistent full-width gallery dropzone — that creates blank space beside details.
