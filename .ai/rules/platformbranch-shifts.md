---
paths:
  - 'app/Http/Controllers/{Platform,Branch}/*ShiftAssignmentController.php,app/Services/Workforce/**,app/Support/WorkforcePayload.php,resources/js/components/admin/shift-*.{ts,tsx},resources/js/pages/{platform,branch}/shifts/assign.tsx'
---

# Platformbranch Shifts

## Assign Shift uses one staged calendar workspace
Use the shared Branch -> User Type -> User -> Calendar workspace. Save additions, replacements, and removals atomically through applyChanges(); target persisted changes by user_shift_days.id, never by date alone. Do not reintroduce Leave, weekly-off, weekday, exception, or legacy edit-form controls on Assign pages.
