---
paths:
  - resources/js/components/file-upload.tsx
---

# Components

## FileUpload preview sync + Strict Mode
FileUpload must re-arm alive.current=true in effect setup (Strict Mode cleanup sets it false). Treat [] as empty controlled value. Sync Object-URL previews from the controlled `value` prop so previews survive remounts and parent setData.

## FileUpload collapses maxFiles skip spam
When maxFiles is exceeded, emit one count rejection summarizing N skipped files — never one message per skipped file. Rejection list UI uses max-h + overflow-y-auto.

## FileUpload hint is human-readable
FileUpload dropzone never dumps raw accept MIME/extension lists into helper text (overflows). Show hint + Max N MB / up to N files only; keep accept for validation/input only.
