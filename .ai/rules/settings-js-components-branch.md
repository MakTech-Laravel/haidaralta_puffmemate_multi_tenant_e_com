---
paths:
  - 'app/Http/Controllers/Branch/GeneralSettingsController.php,resources/js/pages/branch/settings/**,resources/js/components/branch/nav.ts'
---

# Settings Js Components Branch

## Branch General settings is profile-only
Branch Settings → General lives at GET/PUT /branch/{slug}/settings/general (manage-branch-settings). Reuse BranchForm with hideIdentityFields so slug, code, and Active stay platform-owned; the controller merges current slug/code/status into BranchService::update. Hours stays navSoon. Reseed RolesAndPermissionsSeeder so Admin/Manager get the new permission.
