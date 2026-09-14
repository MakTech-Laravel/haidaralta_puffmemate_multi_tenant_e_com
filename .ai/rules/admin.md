---
paths:
  - resources/js/components/admin/branch-form.tsx
---

# Admin

## Branch coords via map dialog picker
Branch create/edit: Address then Map location block with Set on map (BranchLocationPicker dialog) plus optional manual Latitude|Longitude. Do not put an inline live map on the form. Future Mapbox/Google belongs only inside the dialog body; keep onConfirm → form latitude/longitude. Phase 1 confirm stays disabled until a provider can set a pin.
