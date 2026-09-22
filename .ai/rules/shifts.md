---
paths:
  - 'app/Http/Controllers/Platform/*Shift*.php,resources/js/pages/platform/shifts/assign.tsx'
---

# Shifts

## Assign empty page waits for a branch
Branch management Assign without ?branch_id= renders the assign page empty choose-a-branch state (same picker as Branch shifts). Do not redirect to the Branch shifts index. People and templates stay empty until a store is picked. New shift create without branch_id still redirects to the empty board.
