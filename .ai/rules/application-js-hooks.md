---
paths:
  - 'app/Services/Application/ApplicationSettingsService.php,resources/js/hooks/use-app-brand.ts'
---

# Application Js Hooks

## Brand name comes from application settings then env
sharedPayload() includes application.name from application_settings.app_name. HandleInertiaRequests name uses that same value. useAppBrand prefers application.name, then shared name, then VITE_APP_NAME. Do not hardcode Laravel or PuffMeMate in checkout copy.
