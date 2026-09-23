---
paths:
  - 'resources/js/components/admin/staff-*.tsx,resources/js/pages/**/staff/**,resources/js/pages/platform/branch-staff/**'
---

# Branch Staff

## Staff CRUD mirrors admin UI with avatars
Branch staff create/edit uses StaffForm (AdminForm layout + branch select + multi-role checkboxes + avatar FileUpload). List pages show users.avatar via avatar_url. Show pages use StaffMemberProfile (admin details layout with permission_groups). BranchStaffService stores avatars on users.avatar via PublicDiskMedia::USER_AVATARS.
