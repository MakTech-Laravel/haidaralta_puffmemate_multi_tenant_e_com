---
paths:
  - 'app/Services/Driver/**,app/Http/Requests/{Platform,Branch}/*Driver*.php,resources/js/pages/{platform,branch}/drivers/**,resources/js/components/admin/driver-branch-assign-sheet.tsx'
---

# Js Components Admin

## Assignment mode follows config

Assignment count is `config('drivers.single_active_branch')` (env `DRIVER_SINGLE_ACTIVE_BRANCH`, default true). True: one active assignment, transfer deactivates other actives, `branch_ids` max:1, assigner is exclusive select. False: multiple actives, assigner is multi-checkbox. Unique remains `(driver_id, branch_id)` only — never a unique-on-active index. Inertia shares `drivers.single_active_branch`.
