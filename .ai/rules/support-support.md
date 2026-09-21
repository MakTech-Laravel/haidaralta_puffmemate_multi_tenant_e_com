---
paths:
  - 'app/Models/UserShiftAssignment.php,app/Support/DutyWindow.php,app/Support/DriverAvailability.php'
---

# Support Support

## Shifts are duty windows not attendance
Shifts are duty windows for display and who-is-on-duty-now, not attendance, daily rows, overtime, payroll, or completed-duty tracking. user_shift_assignments.branch_id and shift_roster_period_id are required. Overnight wrap is allowed (end <= start → time is >= start OR < end). Effective availability uses scheduled UserShiftAssignment covering now; nested driver_rosters writes on create/update are ignored.
