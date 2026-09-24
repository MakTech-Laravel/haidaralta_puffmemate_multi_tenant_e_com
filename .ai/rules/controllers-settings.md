---
paths:
  - 'resources/js/pages/settings/profile.tsx,app/Http/Controllers/Settings/ProfileController.php'
---

# Controllers Settings

## Settings profile includes avatar
Settings profile form includes avatar (FileUpload variant=avatar) plus name/phone/email. Store via PublicDiskMedia::replace on users/avatars; never expose user_type, branch_id, or is_active for self-edit.
