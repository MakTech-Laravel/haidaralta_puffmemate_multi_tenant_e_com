---
paths:
  - 'app/Http/Requests/**/*BranchArea*,app/Http/Requests/Concerns/ValidatesBranchArea.php,resources/js/components/admin/transfer-branch-area-sheet.tsx,database/seeders/BranchAreaSeeder.php'
---

# Admin Seeders

## Area names unique per branch; Change branch Platform-only
Area name must be unique within a branch (case-insensitive) on create/update via ValidatesBranchArea for Precognition. Different branches may reuse the same name. Platform Change branch drawer (transfer) moves ownership; Branch portal create/edit only. BranchAreaSeeder seeds demo zones after BranchSeeder.
