---
paths:
  - 'app/Services/Driver/**,app/Models/Driver*.php,app/Http/Controllers/{Platform,Branch}/*Driver*.php'
---

# Http Controllers Platform Branch

## Duty coverage uses shift assignments
Who is on duty now comes from today’s `user_shift_days` (`scheduled` / `override`) joined to templates, then DutyWindow on that calendar day. End time must be after start; overnight wrap is not allowed. Nested roster payloads on driver create/update are ignored. Unique remains (driver_id, branch_id) never unique-on-active. config('drivers.single_active_branch') still gates one-active transfers.

## Duty coverage from day rows
Duty coverage for who is on now comes from today’s user_shift_days scheduled/override rows joined to templates, then DutyWindow. Do not scan assignment headers alone.
