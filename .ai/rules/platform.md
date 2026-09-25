---
paths:
  - resources/js/components/platform/product-form.tsx
  - resources/js/components/platform/category-form.tsx
  - 'resources/js/components/platform/{category,product}-form.tsx'
  - resources/js/components/platform/category-profile.tsx
  - resources/js/components/platform/nav.ts
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

Product/category forms use ColorPicker mode=dialog with enabledModes transparent|solid|gradient. Create defaultMode=transparent (no invented fill); edit keeps the stored value. Post JSON.stringify(color_code). Display tiles via colorPickerStyle. Transparent listings inherit the page/card color — do not use COLOR_PICKER_CHECKERBOARD on tiles or picker previews. Do not enable radial until PHP CatalogBackgroundMode supports it.

## Product primary and gallery image types

ProductForm primary + gallery accept PNG, JPG/JPEG, SVG, WebP (max 2MB). Backend ValidatesCatalogProductImages uses image:allow_svg + mimes; serve SVG as img src only.

## Category form matches product media layout

Platform CategoryForm mirrors ProductForm media rail: details left / compact Media right (`xl:grid-cols-[minmax(0,1fr)_minmax(17.5rem,20rem)]`, items-start, no sticky). Square listing photo on the color tile + ColorPicker triggerVariant=button in the footer (no gallery). Create/edit pages use full width p-2 md:p-4. Image accept PNG/JPG/SVG/WebP via ValidatesCatalogProductImages.

## Category media is a listing preview card

CategoryForm Media is a compact rail card: square FileUpload on the color tile (object-contain, compactCards, no filename bar), ColorPicker triggerVariant=button in the footer. Do not equal-column stretch (`xl:grid-cols-2` + min-h) — that leaves blank space beside details.

## Parent category uses searchable combobox

CategoryForm parent_id is a single-select Popover+Command combobox (searchable), with a No parent option that clears parent_id. Match ProductForm combobox styling; close on select.

## Category preview image is centered

Category listing-preview FileUpload card is max-w centered (mx-auto) with square object-contain image. Single-file FileUpload must not force an h-full inner wrapper or vertical centering fails.

## Center category image, no nested card

Category listing preview: do not shrink FileUpload into a nested max-w card. Keep full-width transparent shell; center the image via cardImage object-contain (mx-auto max-h). FileUpload supports classNames.cardImage.

## Branch ProductForm ops fields keep gallery

Branch create/edit use ProductForm with gallery enabled (same as platform). When `catalog.show_branch_pricing` is false (default), product create/edit show catalog selling_price + regular_price on the products row; branch create still offers quantity-only stock seeding. When the flag is true, branchOpsFields adds buying_price, regular_price, selling_price, and quantity on create only (writes branch_product_prices). createFromBranch / update persist gallery via ProductService.

## Catalog forms suggestions + slug Precognition

CategoryForm and ProductForm: enableSuggestions with portal-specific suggestionsUrl; slug uniqueness via Precognition on name/slug blur. Routes need precognitive middleware.

## Product form listing preview and solid red default

ProductForm create defaults color_code to transparent with defaultMode=transparent. Edit keeps the stored value (null → transparent). Media is a narrow rail (not a second equal column): square primary on the color tile, compact ColorPicker in the footer, gallery thumbs with dropzoneVisibility=empty. Do not use min-h-96 or a persistent full-width gallery dropzone — that creates blank space beside details.

## Category show is one listing card

CategoryProfile is a single listing card: compact ProductGallery layout=tile (square ~16rem, card=false) beside facts. Featured, status, created, updated live in Listing details — no Catalog record card, no Media hero, no StatCards. Products jewel stays on the listing header. Subcategory chips only when catalog.show_parent_category.

## Category show background is a fact

CategoryProfile Image background is a fact in the listing grid, not a full-width bar. Transparent is text only (no empty swatch). Solid/gradient show a size-5 swatch beside the summary.

## Branch ops fields omit minimum_stock

branchOpsFields is buying_price + regular_price + selling_price + quantity on branch create. Do not include minimum_stock.

## Administrator shifts nav gated by config
Platform Administrator Shifts + Assign to shift only render when config shifts.show_platform_administrator_shifts (env SHIFTS_SHOW_PLATFORM_ADMINISTRATOR_SHIFTS) is true. Default false. Branch management Shifts/Assign stay visible. Shared via Inertia shifts.show_platform_administrator_shifts into buildPlatformNav.

## Marketing Announcement is live
Platform Marketing shows a live Announcement item after Coupons/Deals at /platform/announcements (Megaphone, manage-reports). Coupons and Deals stay navSoon. No Branch copy of this page.
