---
paths:
  - 'app/Http/Requests/**/*BranchArea*,app/Http/Requests/Concerns/ValidatesBranchArea.php,resources/js/components/admin/transfer-branch-area-sheet.tsx,database/seeders/BranchAreaSeeder.php'
---

# Admin Seeders

## Area names unique per branch; Change branch Platform-only
Area name must be unique globally (case-insensitive) on create/update via ValidatesBranchArea for Precognition. Areas belong to many branches via `branch_area_branch`. Platform “Add to branch” drawer attaches without removing other links; Branch portal create/edit only. BranchAreaSeeder seeds demo zones after BranchSeeder and attaches via the pivot.
