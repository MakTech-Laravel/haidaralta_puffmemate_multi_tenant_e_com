---
paths:
  - 'app/Services/Driver/**'
  - 'app/Models/Driver*.php'
  - 'app/Http/Controllers/{Platform,Branch}/*Driver*.php'
  - 'resources/js/pages/{platform,branch}/drivers/**'
  - 'resources/js/pages/platform/branches/drivers/**'
---

# Driver

## driver_id means users.id
driver_id columns always reference users.id (user_type=driver, branch_id null). driver_profiles.user_id is the 1:1 extension only. Never introduce driver_profile_id on assignments, rosters, or future delivery/location tables. Multi-branch via driver_branch_assignments.

## Driver CRUD surfaces
Platform /platform/drivers is the full driver admin (create requires ≥1 branch_ids). Nested platform.branches.drivers and branch.drivers auto-assign the current branch on create and can assign existing drivers. Rosters require an active assignment for that branch. Overlap is per driver: date ranges overlap AND weekdays intersect AND times overlap (weekday-only vs weekend-only on the same dates is allowed). Roster forms use start/end dates + Mon–Sun day chips (ISO 1–7), DatePicker + TimePicker (HH:mm), not native date/time inputs. No parcel/GPS/chat yet.

## Driver UX: Sheet assigner, media, effective availability
Platform driver assign UI is DriverBranchAssignSheet (right Sheet + server search/pagination via platform.drivers.branch-options). driver_profiles stores address/avatar_path/vehicle_image_path (MediaUrl). List/detail show effective_availability: on_break/busy overrides win, else scheduled period covering today's date + weekday + clock → available, else offline.

## Roster overlap is period plus weekday plus time
One driver_rosters row is a date period (start_date–end_date inclusive) with the same start_time/end_time on days_of_week (ISO 1–7, default all days). Overlap is per driver: date ranges overlap AND weekdays intersect AND times overlap. Weekday-only vs weekend-only on the same dates is allowed. Overnight shifts are invalid. Effective availability: on_break/busy win, else a scheduled period covering today’s date + weekday + clock → available, else offline.
