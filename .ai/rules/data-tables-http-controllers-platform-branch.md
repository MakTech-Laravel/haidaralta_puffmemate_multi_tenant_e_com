---
paths:
  - 'resources/js/components/admin/shift-*.tsx,app/DataTables/ShiftAssignmentDataTable.php,app/Http/Controllers/{Platform,Branch}/*ShiftAssignment*.php'
---

# Data Tables Http Controllers Platform Branch

## Assigned people is a list page
The shifts board has no Scheduled people stat or roster. Assigned people is a monthly DataTable at GET */shifts/assigned-people (named *.shift-assignments.index) so Shifts nav stays active. Hide Assigned people on the choose-a-branch empty state, same as Assign to shift and New shift. Assign create stays off /shifts. One row per person for month=YYYY-MM (default viewer today). Columns are Person (avatar, name, phone), Type (Staff/Driver), Assigned dates (day-of-month runs), Actions. Search/filters are SQL filter[search] (name/phone), filter[shift_template_id], filter[people_kind]. Branch-owned lists wait for ?branch_id=.
