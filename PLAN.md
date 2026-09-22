# Updated Assign Shift Implementation Plan

## Summary

Replace the existing period/weekday/Leave/Override editor with one shared Branch → User Type → User → Calendar workspace.

The implementation will:

- Reuse `shift_templates`, `user_shift_assignments`, and `user_shift_days`; no database migration is required.
- Preserve the three existing authorization contexts: platform-owned shifts, platform branch-owned shifts, and branch portal shifts.
- Use a custom `date-fns` calendar without adding frontend dependencies.
- Support manual and two-click range selection, Ctrl/Cmd manual override, staged assignment/replacement/removal, and one atomic Save.
- Default `SHIFTS_ALLOW_MULTIPLE_PER_DAY` to `false`.
- Target persisted shifts by `user_shift_days.id`, never date alone, so multiple same-day shifts remain unambiguous.
- Remove Leave, weekly-off, exception, main-shift, and weekday controls from the Assign UI.
- Keep historical Leave/weekly-off data readable but stop creating it through this page.

Verified baseline: Laravel 13.30.1, PHP 8.4, PostgreSQL, Inertia 3.3.3, React 19.2, Tailwind 4, Wayfinder, `date-fns`, and `react-day-picker`. The current 34 workforce shift tests pass with 399 assertions.

## 1. Final Architecture Decision

### Retain

- Existing models and tables.
- Existing shift ownership, permission middleware, same-day time-window rules, viewer-timezone handling, and on-duty reads from `user_shift_days`.
- Existing Assign page URLs and POST route names.
- Existing asynchronous branch-options endpoint.
- Existing Assigned People page.

### Replace

- The current form fields, weekday selection, Leave/Different shift dialog, multi-month period editor, and legacy edit mode.
- Full people dumps in Inertia props.
- Date-only replacement/removal operations, which are unsafe when one day has multiple shifts.

### Workspace rules

- Platform-owned Assign: any active branch and active eligible person, but only platform-owned shifts can be assigned or changed.
- Platform branch-management Assign: selected branch and eligible people at that branch; only that branch’s owned shifts can be changed.
- Branch portal Assign: branch is fixed by the route and shown as a read-only first field; both platform shifts available to the branch and that branch’s shifts can be managed.
- Calendar and lower list show all shifts for the selected person and branch. Shifts outside the current workspace’s ownership remain visible but read-only.

## 2. Database Plan

No migration or destructive backfill is needed.

- `user_shift_assignments` remains the contiguous group/header table.
- `user_shift_days` remains the operational source for calendar occupancy, overlap checks, and exact edits.
- New writes use only `kind=scheduled`.
- New canonical headers use `days_of_week=[1,2,3,4,5,6,7]`; the field remains for compatibility.
- `weekly_off`, `leave`, and `override` enum values and historical rows remain intact.
- The existing partial unique index on user, branch, date, and non-null template continues preventing duplicate same-template rows.

### Canonical group normalization

A canonical assignment contains one template and consecutive scheduled dates whose header bounds exactly match its day rows.

For an affected canonical assignment:

1. Remove or move the targeted day rows.
2. Partition remaining dates into consecutive runs.
3. Update the original header with the first run.
4. Create headers for additional runs.
5. Cancel the header when no dates remain.
6. Merge adjacent canonical headers for the same user, branch, and template.

For a legacy assignment containing Leave, weekly-off, overrides, or inconsistent gaps:

1. Lock and cancel the original header as an archive.
2. Keep non-duty rows attached to the cancelled header.
3. Rebuild its remaining on-duty rows as new canonical scheduled groups.
4. Apply the requested change to those canonical groups.

This preserves historical data while ensuring edited schedules become structurally consistent.

## 3. Backend Implementation Plan

### Configuration

Add:

```php
// config/shifts.php
'allow_multiple_per_day' => env('SHIFTS_ALLOW_MULTIPLE_PER_DAY', false),
```

Add `SHIFTS_ALLOW_MULTIPLE_PER_DAY=false` to `.env.example` and share the boolean as `shifts.allow_multiple_per_day` through Inertia. Application code must read `config()`, not `env()`.

### Service layer

Extend `AssignShiftService` with one transaction-oriented operation:

```text
applyChanges(user, branch, targetTemplate?, assignDates, replaceDayIds, removeDayIds, actor, expectedOwner)
```

Processing order:

1. Start a database transaction and lock the selected user row to serialize concurrent schedule writes.
2. Lock referenced shift-day rows and affected assignment headers.
3. Verify every referenced day belongs to the selected user/branch, is editable by the current workspace, remains scheduled, and is not in the past.
4. Build the proposed final schedule in memory:
   - remove `remove_day_ids`;
   - remove the old versions of `replace_day_ids`;
   - add the replacement template on their dates;
   - add the template on `assign_dates`.
5. Validate duplicate targets, ownership, audience, branch membership, active status, config rules, and time overlaps against all untouched shifts for the user, including other branches.
6. Persist removals, replacements, and additions.
7. Normalize/split/shrink/merge affected assignment headers.
8. Record history and commit.

Use transaction retry support for deadlocks. A failed validation or conflict rolls back the entire mixed change-set.

Keep legacy `assign()` and `syncDays()` temporarily for the seeder/backfill command, but route the redesigned page only through `applyChanges()`. Retire the old `change()` HTTP path.

### Time and multiple-shift rules

- Past means earlier than `DateTimeFormat::now()` in the viewer’s timezone; today remains editable.
- Overnight shifts remain unsupported.
- Time windows use `[start, end)` semantics:
  - `05:00–15:00` plus `15:00–22:00` is valid.
  - `05:00–15:00` plus `14:00–20:00` is invalid.
  - `01:00–05:30` plus `05:00–15:00` is invalid.
- When multiple shifts are disabled, a final date may have at most one on-duty shift across all branches.
- When enabled, every pair of final windows for the user/date must be non-overlapping.
- Assigning the same template to a date already containing it is idempotent.
- Replacing multiple existing shifts on the same date with one target is rejected as ambiguous.

### Server-side eligibility

Enforce rather than trusting frontend filtering:

- Staff must be active branch users whose `branch_id` matches.
- Drivers must be active and have an active assignment to the branch.
- Platform and customer accounts cannot be assigned.
- Shift audience must match Staff or Driver, with `all` accepted for both.
- The template must be active, visible at the branch, and owned by the expected workspace.
- Branch portal requests derive the branch from route binding and cannot override it in the payload.

### Payload builders

Extend `WorkforcePayload` with:

- Paginated active people options filtered by branch and `staff|driver`.
- A visible-month calendar payload sourced from on-duty shift days.
- Paginated assignment groups derived from actual on-duty dates rather than trusting legacy header spans.
- Editable flags based on the active workspace.
- Stable ordering and eager loading to prevent N+1 queries.

## 4. Frontend Implementation Plan

### Component architecture

Use one shared workspace rendered by the platform and branch pages:

- `ShiftAssignWorkspace`
- `ShiftAssignmentGate`
- `ShiftPersonPicker`
- `ShiftMonthCalendar`
- `ShiftCalendarDay`
- `ShiftSelectionModeToggle`
- `ShiftDayActions`
- `ShiftChangeDialog`
- `AssignedShiftGroupList`

Enhance `InfiniteScrollCombobox` with `disabled`, an explicit load-error/retry state, and support for preselected labels. Reuse it for Branch and User only; User Type remains a normal Select.

### Header and selector flow

Header:

- Title and concise instructions.
- Back to Shifts and Assigned People actions.
- Selected person summary after the gate is complete.

Selectors:

1. Branch is enabled first on platform pages.
2. User Type is disabled until a branch is selected.
3. User is disabled until User Type is selected.
4. Changing Branch clears Type, User, calendar data, and pending changes.
5. Changing Type clears User, calendar data, and pending changes.
6. Branch portal shows a locked branch field; Type is immediately enabled.
7. Selecting User reloads the workspace with calendar and group data.

Warn before discarding pending changes when changing Branch, Type, User, or month.

### State model

URL/query-driven state:

- `branch_id`
- `people_kind`
- `user_id`
- `month`
- `assignment_page`

Local state:

```text
selectionMode: manual | range
rangeAnchor: date | null
assignDates: Set<date>
replaceDayIds: Set<day-id>
removeDayIds: Set<day-id>
dialogOpen: boolean
targetShiftTemplateId: number | null
```

The default selection mode is Manual.

One Save may atomically contain additions, replacements, and removals. A single chosen target shift applies to all additions and replacements in that Save; removals require no target shift.

### Save workflow

- Save is disabled when all three pending sets are empty.
- If additions or replacements exist, Save opens the shift dialog.
- If only removals exist, Save opens the same dialog in confirmation-only mode.
- Mixed changes show counts for Added, Changed, and Removed before confirmation.
- A failed request keeps the modal and pending state open and displays field or schedule errors.
- Success closes the dialog, clears pending state, shows the success toast, and reloads calendar/groups through the redirected Inertia page.

## 5. Calendar Technical Design

Build a custom month grid with `date-fns`, reusing concepts from the current custom `MonthCard`; do not add another calendar package.

### Rendering

- Monday-first seven-column grid.
- Previous/next buttons and month/year controls.
- Initial month is the viewer’s current month.
- Adjacent-month cells remain visible but muted.
- Navigation into past months is allowed for history viewing.
- Responsive cells with accessible buttons, labels, focus rings, and keyboard navigation.
- Use the application primary theme for red/brand states rather than hard-coded colour values.

### Visual states

- Past unassigned: muted gray, disabled.
- Past assigned: primary/red at reduced opacity, no edit controls.
- Future assigned: primary/red with shift chips or count.
- Today: distinct border/ring.
- Pending new assignment: primary outline/light fill.
- Pending replacement: stronger ring and “Changing” state.
- Pending removal: desaturated/struck-through chip with Undo.
- Multiple shifts: individual chips/actions; compact cells may show the first entries plus a count.

### Selection behavior

Manual mode:

- Clicking an unassigned future date toggles it in `assignDates`.
- Clicking a pending date again removes only that date.
- Clicking a date with one editable shift stages that exact shift-day ID for replacement.
- Clicking a date with multiple shifts opens its individual actions.
- “Add another shift” appears only when multiple shifts are enabled.

Range mode:

- First unassigned future click sets the anchor.
- Second click selects the inclusive normalized range, regardless of click order.
- Past dates are never included.
- Already assigned dates are not implicitly replaced or given another shift; they are skipped and reported, because selecting by date alone is ambiguous.
- Clicking any already-pending date removes only that date; range removal never occurs automatically.

Ctrl/Cmd:

- In Range mode, Ctrl/Cmd-click temporarily performs a one-date manual toggle.
- In Manual mode, it behaves like the regular toggle.
- Touch devices use the visible mode toggle and per-day actions.

Removal:

- Each persisted editable shift chip has a cross.
- The cross stages its specific `user_shift_day` ID for removal.
- Clicking Undo restores it.
- Nothing is deleted before Save.

## 6. Exact HTTP and Inertia Contracts

### Branch options

Reuse the existing platform branch-options endpoint:

```text
GET /platform/branch-shift-branch-options
?filter[search]=syd&page=1&per_page=20
```

```json
{
  "data": [
    { "id": 1, "name": "PuffMeMate Sydney CBD", "slug": "sydney-cbd" }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

### User options

Add equivalent endpoints for the three workspaces:

```text
GET /platform/shift-assignment-people-options
GET /platform/branch-shift-assignment-people-options
GET /branch/{branch}/shift-assignment-people-options
```

Platform query:

```text
branch_id=1&people_kind=staff|driver&filter[search]=alex&page=1&per_page=20
```

Branch portal derives `branch_id` from the route.

```json
{
  "data": [
    {
      "id": 42,
      "name": "Alex Morgan",
      "user_type": "branch",
      "avatar_url": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 20,
    "total": 27
  }
}
```

### Load schedule

Use the existing Assign GET endpoints with query state rather than a parallel API:

```text
GET /platform/shift-assignments
GET /platform/branch-shift-assignments
GET /branch/{branch}/shift-assignments
```

Example query:

```text
?branch_id=1&people_kind=driver&user_id=42&month=2026-04&assignment_page=1
```

Key Inertia props:

```json
{
  "selectedBranch": { "id": 1, "name": "Sydney CBD", "slug": "sydney-cbd" },
  "selectedPeopleKind": "driver",
  "selectedPerson": {
    "id": 42,
    "name": "Alex Morgan",
    "user_type": "driver",
    "avatar_url": null
  },
  "templates": [
    {
      "id": 7,
      "name": "Dispatch",
      "start_time": "05:00",
      "end_time": "15:00",
      "audience": "driver"
    }
  ],
  "calendar": {
    "month": "2026-04",
    "today": "2026-04-09",
    "grid_start": "2026-03-30",
    "grid_end": "2026-05-10",
    "days": {
      "2026-04-10": [
        {
          "id": 101,
          "assignment_id": 55,
          "shift_template_id": 7,
          "name": "Dispatch",
          "start_time": "05:00",
          "end_time": "15:00",
          "editable": true
        }
      ]
    }
  },
  "assignmentGroups": {
    "data": [
      {
        "key": "7:2026-04-07:2026-04-19",
        "shift_template_id": 7,
        "name": "Dispatch",
        "start_time": "05:00",
        "end_time": "15:00",
        "start_date": "2026-04-07",
        "end_date": "2026-04-19",
        "date_count": 13,
        "editable": true,
        "is_past": false
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 20,
      "total": 1
    }
  }
}
```

Groups are ordered: current/upcoming ascending by start date, then past descending by end date.

### Atomic assign/update/remove

Repurpose the existing POST Assign routes with a new request contract:

```json
{
  "branch_id": 1,
  "user_id": 42,
  "shift_template_id": 7,
  "assign_dates": ["2026-04-07", "2026-04-08"],
  "replace_day_ids": [101],
  "remove_day_ids": [102, 103],
  "month": "2026-04",
  "assignment_page": 1
}
```

Rules:

- `branch_id` is required for platform routes and prohibited/ignored for branch portal routes.
- `user_id` is required.
- At least one change array must contain an item.
- Arrays contain distinct values.
- `shift_template_id` is required when assigning or replacing and nullable for removal-only saves.
- At most 366 distinct affected dates, with a maximum earliest-to-latest span of 366 days.
- Replacement and removal IDs must be disjoint.
- A date cannot be represented by contradictory assign/replace/remove operations.

Responses:

- Success: `303 See Other` to the canonical Assign GET URL with the selected query, refreshed props, and success toast.
- Form validation: Inertia validation errors.
- Schedule/business conflict: validation error under `schedule`, leaving modal state intact.
- Unauthorized or cross-tenant day/template: `404`.
- Entire change-set rolls back if any target fails.

Legacy edit GET routes redirect to the canonical Assign page with branch, person, and relevant month selected. Remove the obsolete PUT update routes and their old Wayfinder calls after all internal links are changed.

## 7. Business Rules

- User-facing copy always says “shift,” never “template.”
- Leave, weekly off, day exceptions, weekday patterns, and roster periods are absent from the redesigned page.
- New assignment dates may be single, consecutive, or non-consecutive.
- Range selection affects only assignment selection; deselection/removal is always per date or per shift chip.
- A specific persisted shift must be identified by day ID before it can be changed or removed.
- Multiple shifts default to disabled.
- Enabling multiple shifts exposes Add another shift but never bypasses overlap validation.
- Inactive shifts display on historical assignments but cannot be selected for new work.
- Past assignments remain visible and paginated but cannot be modified.
- The separate Assigned People page remains and its edit action opens the new workspace rather than the legacy editor.

## 8. Edge Cases and Failure Handling

- Duplicate date/template assignment: no-op rather than duplicate row.
- Stale replacement/removal ID: reject the entire batch with a schedule conflict.
- Middle-date removal: split the old group into two groups.
- End-date removal: shrink the group.
- Removing every date: cancel the header.
- Replacing date 19 in 7–19: old group becomes 7–18; 19 becomes a new target-shift group.
- Mixed add/change/remove: validate final state first and commit atomically.
- Assigned dates encountered during range selection: skip rather than guessing add/replace.
- Cross-month range: allowed within the 366-day guard; calendar moves only when the user navigates.
- User/template changed in another session: transaction locks and final-state validation prevent stale overwrites.
- Network failure: retain dialog and pending changes with retry.
- Options load failure: show an inline retry state instead of silently appearing empty.
- No available shifts for selected audience/workspace: keep Save disabled and show a create/activate-shift explanation.
- Changing selectors with pending work: require confirmation before clearing it.

## 9. Testing Strategy

### Backend unit tests

- Consecutive-run grouping for single, adjacent, non-adjacent, and cross-month dates.
- Split, shrink, cancel, and adjacent-group merge.
- Legacy assignment canonicalization while preserving non-duty historical rows.
- Proposed final schedule validation and `[start,end)` overlap boundaries.

### Pest feature/API tests

- Branch and user option pagination, search, active filtering, type filtering, and stable ordering.
- Guest, missing-permission, wrong-branch, wrong-owner, and cross-tenant failures.
- Empty gate props before branch/user selection.
- Calendar and paginated-group payload shapes.
- Single, range, and non-consecutive assignment writes.
- End and middle replacement/removal.
- Successful mixed atomic save.
- One invalid mixed target rolls back every change.
- Default config rejects a second same-day shift.
- Enabled config accepts adjacent shifts and rejects overlaps.
- Audience mismatch, inactive template, duplicate IDs, contradictory operations, invalid dates, and past-date rejection.
- Viewer-timezone boundary: yesterday rejected and today accepted.
- Read-only foreign-owner shifts are visible but cannot be mutated.
- Legacy edit URLs redirect to the new workspace.
- Existing seeder/backfill compatibility remains covered.

### Frontend verification

No new test dependencies are approved in this plan. Therefore:

- Extract the calendar selection reducer/state transitions into a pure TypeScript module for maintainability.
- Run `pnpm check`, `pnpm types:check`, and `pnpm build`.
- Manually verify desktop, keyboard, touch, 390px mobile, and desktop layouts.
- QA manual/range switching, reverse range, Ctrl/Cmd override, per-shift actions, staged undo, mixed save, modal errors, past dates, multiple-shift count, selector resets, pagination, and cross-month navigation.
- A Vitest/jsdom/Testing Library harness is a separate dependency-approved follow-up.

Run focused Pest tests after each backend change, format PHP with `vendor/bin/pint --dirty --format agent`, then request a complete `php artisan test --compact` run.

## 10. Implementation Phases

1. **Contracts and configuration**
   - Add shift config/env/shared type.
   - Define query and mutation request objects.
   - Add people-options routes and exact payload types.
   - Update conflicting `.ai/rules` only when the new behavior is implemented.

2. **Domain transaction**
   - Add date-run grouping and canonicalization support.
   - Implement `applyChanges()` with user-row locking, final-state validation, history, and atomic rollback.
   - Add focused unit and feature tests before controller integration.

3. **Controller and payload layer**
   - Replace legacy Assign form props with selector/calendar/group props.
   - Wire paginated people, month data, groups, and atomic POST handling.
   - Preserve workspace ownership differences and edit redirects.
   - Regenerate Wayfinder with `php artisan wayfinder:generate --with-form --no-interaction`.

4. **Shared frontend workspace**
   - Add the cascading selectors and locked branch-portal presentation.
   - Add custom month calendar, selection reducer, day actions, dialog, and paginated groups.
   - Remove old Leave/weekday/exception UI from the Assign path.

5. **Integration and hardening**
   - Add pending-change navigation guards, retry/error states, responsive polish, and accessible keyboard behavior.
   - Update Assigned People edit links.
   - Verify all config states and all three workspaces.

6. **Final verification**
   - Run focused Pest coverage, Pint, frontend checks, types, and production build.
   - Review the complete diff before any deployment.
   - Deployment requires no migration; set or confirm `SHIFTS_ALLOW_MULTIPLE_PER_DAY=false`, clear/cache configuration as appropriate, and smoke-test each workspace.

## Assumptions Locked

- Multiple shifts are disabled by default.
- Mixed changes are saved atomically.
- Ctrl/Cmd temporarily enables one-date toggling in Range mode.
- Assignment groups include all history and use pagination.
- Branch portal shows a read-only branch field.
- Manual selection is the default.
- Theme primary provides the highlighted red treatment.
- No frontend dependency additions are included.
- Historical Leave/weekly-off data is preserved but not editable through this page.
