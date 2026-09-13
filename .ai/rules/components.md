---
paths:
  - resources/js/components/file-upload.tsx
---

# Components

## FileUpload preview sync + Strict Mode
FileUpload must re-arm alive.current=true in effect setup (Strict Mode cleanup sets it false). Treat [] as empty controlled value. Sync Object-URL previews from the controlled `value` prop so previews survive remounts and parent setData.
