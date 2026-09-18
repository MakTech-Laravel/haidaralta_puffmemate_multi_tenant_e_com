---
paths:
    - resources/js/components/file-upload.tsx
    - resources/js/components/product-gallery.tsx
    - resources/js/components/permission-selector.tsx
---

# Components

## FileUpload preview sync + Strict Mode

FileUpload must re-arm alive.current=true in effect setup (Strict Mode cleanup sets it false). Treat [] as empty controlled value. Sync Object-URL previews from the controlled `value` prop so previews survive remounts and parent setData.

## FileUpload collapses maxFiles skip spam

When maxFiles is exceeded, emit one count rejection summarizing N skipped files — never one message per skipped file. Rejection list UI uses max-h + overflow-y-auto.

## FileUpload hint is human-readable

FileUpload dropzone never dumps raw accept MIME/extension lists into helper text (overflows). Show hint + Max N MB / up to N files only; keep accept for validation/input only.

## Compact gallery with lightbox

Product show gallery uses compact thumbnails + yet-another-react-lightbox click-to-preview. Platform catalog product show and branch/platform product manage Listing details use ProductGallery layout=tile in the listing rail (square + horizontal thumbs). Do not give product show a half-page hero Media card.

## Gallery/primary use color_code tile

Product primary + gallery thumbnails/lightbox use product.color_code via colorPickerStyle (object-contain). Transparent mode inherits the page or card color — never COLOR_PICKER_CHECKERBOARD on listing tiles or picker previews. Checkerboard stays only on the hex swatch editor. Apply on platform catalog show and branch/platform inventory show.

## FileUpload empty dropzone uses add tile

FileUpload dropzoneVisibility=empty hides the dashed dropzone once files exist and shows a compact Add tile in the grid (opacity-0 input overlay, not programmatic click). compactCards hides filename/size (square thumbs). Default dropzoneVisibility=always so other callers keep the large zone.

## Gallery tile layout is compact square

ProductGallery layout=tile is a compact square click-to-preview. When more than one media item exists, show a horizontal scrolling thumb strip under the square (size-10, not a 4-col grid). Category show passes images=[] so thumbs stay hidden. Product catalog show uses tile in the listing rail. layout=hero stays a larger square + thumbs for other callers.

## FileUpload survives Inertia File cloneDeep

Inertia useForm setData('key', value) cloneDeep's the whole form (es-toolkit new File([file], …)), so File refs change on every keystroke. FileUpload preview sync must match by name+size+type and reuse Object URLs/keys — never revoke/rebuild solely because preview.file !== value[i]. Prefer functional setData(current => ({...current, field})) when updating non-file fields beside large uploads.

## Tile thumbs are a strip, not a grid

ProductGallery layout=tile may include a horizontal thumb strip when gallery images exist. Keep thumbs size-10 and overflow-x-auto so a 14–16rem rail does not grow taller than the listing facts. Never use a wrapping 4-col thumb grid or a 50/50 Media card on product show.

## Permission picker is a compact two-pane

Role permission assignment is a fixed-height two-pane picker (group nav + dense checkbox rows), not stacked accordion cards or two-column permission grids. Keep posting selected values as hidden permissions[] inputs. Search filters groups and rows.
