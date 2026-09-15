---
paths:
  - resources/js/components/file-upload.tsx
  - resources/js/components/product-gallery.tsx
---

# Components

## FileUpload preview sync + Strict Mode
FileUpload must re-arm alive.current=true in effect setup (Strict Mode cleanup sets it false). Treat [] as empty controlled value. Sync Object-URL previews from the controlled `value` prop so previews survive remounts and parent setData.

## FileUpload collapses maxFiles skip spam
When maxFiles is exceeded, emit one count rejection summarizing N skipped files — never one message per skipped file. Rejection list UI uses max-h + overflow-y-auto.

## FileUpload hint is human-readable
FileUpload dropzone never dumps raw accept MIME/extension lists into helper text (overflows). Show hint + Max N MB / up to N files only; keep accept for validation/input only.

## Compact gallery with lightbox
Product show gallery uses compact thumbnails (dense grid) + yet-another-react-lightbox click-to-preview. Platform catalog product show uses ProductGallery layout=hero (large primary stage + thumbs). BranchProductShowPayload includes gallery; branch/platform inventory show keep the compact grid.

## Gallery/primary use color_code tile
Product primary + gallery thumbnails/lightbox use product.color_code via colorPickerStyle (object-contain). Transparent mode inherits the page or card color — never COLOR_PICKER_CHECKERBOARD on listing tiles or picker previews. Checkerboard stays only on the hex swatch editor. Apply on platform catalog show and branch/platform inventory show.

## FileUpload empty dropzone uses add tile
FileUpload dropzoneVisibility=empty hides the dashed dropzone once files exist and shows a compact Add tile in the grid (opacity-0 input overlay, not programmatic click). compactCards hides filename/size (square thumbs). Default dropzoneVisibility=always so other callers keep the large zone.
