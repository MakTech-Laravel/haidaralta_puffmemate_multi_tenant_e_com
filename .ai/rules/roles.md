---
paths:
    - 'resources/js/components/admin/role-*.tsx, resources/js/pages/{platform,branch}/roles/**'
---

# Roles

## Role form and profile match admin layout

Role create/edit uses shared RoleForm at full width (flex w-full, rounded-2xl sections), not max-w-3xl. Role show uses shared RoleProfile: identity rail (Shield, System/Custom, counts), grouped check/X permissions, assigned users table. Platform and branch pages share both components. Keep Precognition name uniqueness on blur with validationTimeout={500}. Delete only when not super-admin and no assigned users.
