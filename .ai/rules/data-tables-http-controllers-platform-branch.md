---
paths:
  - 'resources/js/components/admin/shift-*.tsx,app/DataTables/ShiftAssignmentDataTable.php,app/Http/Controllers/{Platform,Branch}/*ShiftAssignment*.php'
---

# Data Tables Http Controllers Platform Branch

## Assigned people is a list page
The shifts board has no Scheduled people stat or roster. Assigned people is a DataTable at GET */shifts/assigned-people (named *.shift-assignments.index) so Shifts nav stays active. Hide Assigned people on the choose-a-branch empty state, same as Assign to shift and New shift. Assign create stays off /shifts. Columns are Person, Dates (one day or start–end), Shift — no staff/driver badges. Search/filters are SQL filter[search], filter[shift_template_id], filter[people_kind]. Branch-owned lists wait for ?branch_id=.
