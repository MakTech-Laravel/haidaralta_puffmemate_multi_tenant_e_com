---
paths:
  - 'resources/js/components/admin/staff-form.tsx,app/Http/Requests/{Platform,Branch}/*BranchStaff*Request.php'
---

# Admin Http Requests Platform Branch

## Staff form is single-role like admin
Branch staff create/edit assigns exactly one role (radio list + roles max:1), same as platform AdminForm. Do not restore multi-role checkboxes on StaffForm.
