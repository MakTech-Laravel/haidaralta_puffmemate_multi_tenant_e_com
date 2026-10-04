---
paths:
  - 'app/Models/BranchArea*.php,app/Services/Branch/BranchAreaService.php,app/Http/Controllers/Platform/BranchAreasController.php,resources/js/components/admin/transfer-branch-area-sheet.tsx'
---

# Platform Js Components Admin

## Area transfer is Platform-only with history
An area (hub/suburb) has exactly one branch_id. Moving ownership uses POST /platform/branch-areas/{area}/transfer (edit-branches only): updates branch_id and appends branch_area_transfer_histories (from/to/effective_date/reason/transferred_by). Do not change branch_id via edit form. Branch portal cannot transfer. Checkout/auto-branch selection stays unwired.
