---
paths:
  - resources/js/components/admin/branch-form.tsx
  - resources/js/components/admin/driver-form.tsx
  - resources/js/components/admin/duty-days-picker.tsx
  - resources/js/components/admin/driver-roster-fields.tsx
  - resources/js/components/admin/branch-location-map-dialog.tsx
  - resources/js/components/admin/driver-branch-assign-sheet.tsx
  - 'resources/js/components/admin/shift-*.tsx'
---

# Admin

## Branch coords via map dialog picker

Branch create/edit: Address then Map location block with Set on map (BranchLocationPicker dialog) plus optional manual Latitude|Longitude. Do not put an inline live map on the form. Future Mapbox/Google belongs only inside the dialog body; keep onConfirm → form latitude/longitude. Phase 1 confirm stays disabled until a provider can set a pin.

## DriverForm layout matches BranchForm

DriverForm follows BranchForm: 2-col field rows (not 3-col), placeholders on every Input/Textarea/Select, SelectTrigger className w-full (component default is w-fit). Add shift is never disabled; with no assigned branches it opens DriverBranchAssignSheet then appends a roster row.

## DriverForm duty periods

Duty shifts show whenever rosterStatuses is passed (nested create/edit lock the current branch). Each row is a date period (start/end dates, same hours, Mon–Sun chips + All days/Weekdays). Start DatePicker max is end_date; end DatePicker min is start_date (same-day allowed). Edit appends filled rows; existing periods stay on the detail page.

## DriverForm avatar caption and status cards

Driver avatar uses FileUpload variant=avatar with showCaption={false} (no Photo/hint column). Profile & vehicle: license full width, then vehicle type | availability, then Active/Inactive card radios — those three rows sit beside and stretch to the vehicle image.

## View-only branch map dialog uses OSM embed

BranchLocationMapDialog is view-only. Preview uses an OpenStreetMap embed until Mapbox/Google is wired. Keep picking on BranchLocationPicker (create/edit). Do not put a live map inline on branch show or the branch form.

## Assigner select mode follows shared flag
DriverBranchAssignSheet and driver form/pages copy read Inertia drivers.single_active_branch. True: exclusive select and one-branch/transfer copy. False: multi-checkbox and manage-branches copy. Do not hardcode one-branch UI.

## One Branch dropdown plus type
Branch management shifts use one Branch dropdown — pick a store, reload that store — plus a Type filter beside it (All / Staff only / Drivers only). No All-branches chips or extra filter bars.

## Shift branch picker pages 20 at a time
Branch shifts picker is InfiniteScrollCombobox against platform.branch-shift-templates.branch-options (20 per page, filter[search], append on scroll). Never dump every branch into Inertia props. Opening Branch shifts with no `?branch_id=` shows an empty choose-a-branch state — do not auto-select a store.

## Say shift, not template
User-facing shift copy says shift, never template. Keep ShiftTemplate in code, routes, and props. Buttons, headings, empty states, and toasts use New shift / Create shift / Shift created.

## No overnight shift UI
Shift board, coverage map, and forms never show overnight wrap, Overnight stats, or Overnight filters. End must be after start on the same day. User-facing copy still says shift, never template.

## No default branch on Branch shifts
Opening Branch shifts with no ?branch_id= shows an empty choose-a-branch state. Do not auto-select a store. After picking a branch, reload that store only.

## Assign is a personal calendar, not a roster period
The Assign page is a full-width calendar: person, dates, main shift, working days. Choosing shift + dates fills working days immediately. A day can be Keep, Leave, or a different shift; Apply to a date span covers multi-day leave. There is no New period, roster-period list, or period form. Other assignments for that person show as read-only chips. Covering someone is a second assignment.

## Assign calendar has no period UI
Assign is a full-width personal calendar: person, dates, main shift, working days. Fill working days immediately. Day actions are Keep, Leave this day, or Different shift, plus Apply to a date span. No New period or roster-period UI. Other assignments for that person are read-only chips. Covering someone is a second assignment.

## Filled board filters by named shift
After a store is chosen, Today's coverage sits under the branch picker and Type filter. Default named-shift filter is All shifts. Named-shift chips (and coverage lanes) scope stats, on-duty, people, and the shifts list to that window. The Shifts stat card becomes that window's hours when one shift is selected. These chips are shift filters, not All-branches chips.

## Scheduled people is a capped roster
Scheduled people is a compact scrolling roster (max-height, 20 then Show more, search, grouped by person type). Do not render a card grid of every assignment. The named-shift filter lives in that roster toolbar as All shifts select, not as chips under coverage. Coverage always draws every window on a 12-hour clock (12a through 12a, one mark per hour). Selecting a shift dims other lanes but does not hide hours or switch the axis to 0–24.

## Board type filter beside branch
Type filter sits beside the Branch picker (default All). Staff = audience all|staff and branch people; Drivers = all|driver and drivers. It scopes coverage, stats, on duty, scheduled people, and the shifts list. Roster no longer has a people-type select. Uncovered today is gone from this page.
