---
paths:
  - resources/js/components/admin/branch-form.tsx
  - resources/js/components/admin/driver-form.tsx
  - resources/js/components/admin/duty-days-picker.tsx
  - resources/js/components/admin/driver-roster-fields.tsx
  - resources/js/components/admin/branch-location-map-dialog.tsx
  - resources/js/components/admin/driver-branch-assign-sheet.tsx
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
