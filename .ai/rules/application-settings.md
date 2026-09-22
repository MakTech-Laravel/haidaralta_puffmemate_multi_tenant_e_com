---
paths:
  - 'app/Services/Application/**'
  - app/Models/ApplicationSetting.php
- app/Models/SmtpSetting.php
- app/Models/PaymentGatewaySetting.php
- app/Models/ContactSetting.php
- 'app/Http/Controllers/Platform/*SettingsController.php'
  - 'app/Http/Requests/Platform/*Settings*.php'
  - app/Support/DateTimeFormat.php
  - 'resources/js/pages/platform/settings/**'
  - resources/js/lib/datetime.ts
  - 'database/migrations/*_create_application_settings_table.php'
- 'database/migrations/*_create_smtp_settings_table.php'
- 'database/migrations/*_create_payment_gateway_settings_table.php'
- 'database/migrations/*_create_contact_settings_table.php'
  - 'app/Http/Controllers/Platform/*Shift*.php'
  - 'database/migrations/**'
  - app/Support/DriverAvailability.php
---

# Application settings

Platform Application settings live under `/platform/settings/*` with permission `manage-application-settings`.

## Stores

- `application_settings` singleton: branding, logo/favicon/PWA paths (`pwa_icon_192`, `pwa_icon_512`, `pwa_maskable`, `apple_touch_icon`), theme colors, date/time **format** strings. No slogan column. No fixed display timezone.
- General media fields must use shared `FileUpload` + `ExistingFile` (same pattern as branch/category forms) — never ad-hoc `<input type="file">` media widgets.
- `smtp_settings` singleton: encrypted password, runtime mail overlay only when `enabled`.
- `payment_gateway_settings`: fixed keys `cod`, `pos`, `payment_link` — label + enabled (+ optional description) only in v1.
- `contact_settings` singleton: public contact info (`phone`, `fax`, `email`, `whatsapp`, `address`, `map_embed_url`, `socials` JSON). Shared to Inertia as `contacts` for storefront Contact/Wholesale/Footer.

Never reuse ERP `system_settings`. Never write `APP_ENV` / `APP_DEBUG` from the UI (read-only status). Use Laravel maintenance mode for ops downtime. Keep `config/app.php` timezone `UTC` for storage. Display timezone is **viewer-based**: browser IANA zone synced via `app_timezone` cookie (and optional `X-Timezone` header), resolved in `DateTimeFormat` / `resources/js/lib/datetime.ts`.

## Consumers

Boot overlays in `AppServiceProvider`. Share `application` + `contacts` + `paymentGateways` from `HandleInertiaRequests`. Manifest/favicon/apple-touch/logo read overlays. Checkout payment methods come from `PaymentGatewaySettingsService::enabledMethods()`.

## Frontend display rules

- Dates/times: use `AppDate` / `AppTime` / `AppDateTime` / `AppDateRange` from `resources/js/components/app-datetime.tsx`, or `useAppDateTime()` / `formatAppDate*` / `formatAppClock` from `resources/js/lib/datetime.ts` (formats from shared `application` props; timezone from the viewer browser + `syncViewerTimezoneCookie()`). Never hardcode `toLocale*` / `Intl.DateTimeFormat` for product timestamps except inside `datetime.ts`.
- Branding: use `useAppBrand()` for logo/name/description across storefront, auth, driver, and portal shells. `ApplicationTheme` syncs `--primary` from `themeColor`.
- Backend display strings: use `App\Support\DateTimeFormat` (viewer cookie/header, UTC fallback).
- SMTP: when `smtp_settings.enabled`, overlay mailer + from + reply_to globally; `ApplyMailReplyTo` listener stamps reply-to on outbound mail. Backup notification from address is overlaid too.
- Maintenance mode excepts `platform` / `platform/*` so ops can disable it; optional `MAINTENANCE_SECRET` bypass.

## On-duty uses viewer timezone

On-duty and DriverAvailability compare windows against `DateTimeFormat::at()` (viewer timezone from cookie/header), not UTC now(). `config/app.php` stays UTC for storage. Tests that freeze clock times must set the viewer timezone on the request (cookie or `X-Timezone`) then `travelTo` a wall-clock instant in `DateTimeFormat::displayTimezone()`.
