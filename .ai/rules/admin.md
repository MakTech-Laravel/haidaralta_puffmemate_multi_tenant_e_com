---
paths:
  - resources/js/components/admin/branch-form.tsx
  - resources/js/components/admin/driver-form.tsx
---

# Admin

## Branch coords via map dialog picker
Branch create/edit: Address then Map location block with Set on map (BranchLocationPicker dialog) plus optional manual Latitude|Longitude. Do not put an inline live map on the form. Future Mapbox/Google belongs only inside the dialog body; keep onConfirm → form latitude/longitude. Phase 1 confirm stays disabled until a provider can set a pin.

## DriverForm layout matches BranchForm
DriverForm follows BranchForm: 2-col field rows (not 3-col), placeholders on every Input/Textarea/Select, SelectTrigger className w-full (component default is w-fit). Add shift is never disabled; with no assigned branches it opens DriverBranchAssignSheet then appends a roster row.

## DriverForm avatar caption and status cards
Driver avatar uses FileUpload variant=avatar with showCaption={false} (no Photo/hint column). Profile & vehicle: license full width, then vehicle type | availability, then Active/Inactive card radios — those three rows sit beside and stretch to the vehicle image.
