---
paths:
  - 'app/Models/UserShiftAssignment.php,app/Support/DutyWindow.php,app/Support/DriverAvailability.php'
---

# Support Support

## Shifts are duty windows not attendance
Shifts are duty windows for display and who-is-on-duty-now, not attendance, overtime, payroll, or clock-in. On-duty and DriverAvailability read today’s `user_shift_days` rows (`scheduled` / `override`) joined to templates, then `DutyWindow::coversTime`. There is no `shift_roster_period_id`. End time must be after start time on the same day — no overnight wrap, no is_overnight column. Nested driver_rosters writes on create/update are ignored.

## On-duty reads user_shift_days
On-duty and DriverAvailability read today’s user_shift_days (scheduled/override) joined to templates, then DutyWindow::coversTime. There is no shift_roster_period_id. Shifts are still duty windows, not attendance or payroll. Nested driver_rosters writes stay ignored.
