---
paths:
  - 'resources/js/layouts/settings/**,resources/js/pages/settings/**,app/Http/Controllers/Settings/ProfileController.php'
---

# Http Controllers Settings

## Branch settings skip SettingsLayout and delete
Branch /settings/profile and /settings/security use BranchLayout only (no Settings heading or Profile/Security underline tabs). Restyle with the same page Heading + pad as other Branch pages. Do not render DeleteUser or allow profile.destroy for UserType::BRANCH. Platform keeps SettingsLayout tabs.
