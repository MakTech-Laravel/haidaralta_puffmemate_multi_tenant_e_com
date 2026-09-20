---
paths:
  - 'resources/js/pages/driver/profile/**'
---

# Profile

## Driver edit details mirrors the profile page
GET /driver/profile/edit is an editable version of the Profile tab under DriverLayout: accent identity strip (FileUpload avatar with initials fallback, heading name, Save), a Contact 2-col icon field grid, then a Vehicle FileUpload image well and type select. Use FileUpload for photo previews (it keeps Object URLs through Inertia cloneDeep). Do not pass isUploading or uploadProgress into FileUpload here — the Save spinner is the only save progress UI. Do not restore a custom blob-URL <img>, dashed Photo island, a You card, Back to profile, Cancel, or a max-w-2xl form. Profile tab is the back path.
