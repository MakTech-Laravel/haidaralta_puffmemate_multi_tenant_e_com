---
paths:
  - 'resources/js/components/admin/shift-*-empty-state.tsx,resources/js/pages/platform/shifts/assign.tsx'
---

# Platform Shifts

## Assign empty waits for Branch Type User
Assign empty is the cascading gate (Branch → User Type → User) inside ShiftAssignWorkspace, not ShiftBranchEmptyState coverage bars and not the old Keep/Leave sample. Branch portal locks Branch as read-only. Do not restore the legacy period empty state.
