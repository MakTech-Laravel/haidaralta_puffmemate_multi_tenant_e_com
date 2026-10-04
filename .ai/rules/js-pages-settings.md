---
paths:
  - resources/js/pages/settings/profile.tsx
---

# Js Pages Settings

## Settings profile matches customer account layout
On `/settings/profile` (branch and platform), keep the avatar upload on the left (`shrink-0`, FileUpload `variant="avatar"`) and put name / phone / email in a `min-w-0 flex-1` column so fields fill the remaining width. Stack on small screens (`flex-col` → `sm:flex-row`). Put the Save button inside the fields column (not as a separate form footer). Staff avatar size stays 112px; non-staff keeps the larger avatar.
