---
paths:
  - 'resources/js/components/admin/shift-*.{ts,tsx},resources/js/pages/{platform,branch}/shifts/assign.tsx'
---

# Pagesplatformbranch Shifts

## Assign calendar is the only schedule view
Do not show a bottom Assigned shifts group list on Assign. Shifts appear only on the month calendar. Whole day cells must be clickable for selection; only the shift action menu (and Add another) stop propagation.
