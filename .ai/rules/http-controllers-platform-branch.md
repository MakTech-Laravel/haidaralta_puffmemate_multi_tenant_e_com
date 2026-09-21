---
paths:
  - 'app/Services/Driver/**,app/Models/Driver*.php,app/Http/Controllers/{Platform,Branch}/*Driver*.php'
---

# Http Controllers Platform Branch

## Duty coverage uses shift assignments
Who is on duty now comes from scheduled user_shift_assignments + DutyWindow (overnight wrap allowed). Nested roster payloads on driver create/update are ignored. Unique remains (driver_id, branch_id) never unique-on-active. config('drivers.single_active_branch') still gates one-active transfers.
