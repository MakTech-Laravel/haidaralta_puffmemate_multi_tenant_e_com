---
paths:
  - 'resources/js/components/manage-two-factor.tsx,resources/js/components/manage-passkeys.tsx'
---

# Js Components

## 2FA and Passkeys use settings section cards
ManageTwoFactor and ManagePasskeys wrap in the same rounded border card as Change Password / identity (`border-border/80 bg-card rounded-2xl border p-5 shadow-sm`). Do not render them as bare headings on the page. Passkeys keeps its inner list/empty box.
