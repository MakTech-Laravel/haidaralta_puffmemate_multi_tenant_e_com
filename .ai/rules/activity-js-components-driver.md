---
paths:
  - 'resources/js/pages/driver/activity/**,resources/js/components/driver/driver-activity.ts'
---

# Activity Js Components Driver

## Driver activity is a field feed not a data-table
Driver activity index keeps the ActivityLogDataTable `table` Inertia payload for tests, but must not render DataTable, DataTableToolbar, or ActivityLogTable. Use a day-grouped feed with chips, search, and simple pagination. The show page is a readable detail, not an admin JSON dump.
