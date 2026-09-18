---
paths:
    - 'resources/js/actions/**'
---

# Actions

## Wayfinder generate needs --with-form

Always run `php artisan wayfinder:generate --with-form` (or rely on Vite wayfinder formVariants). Generating without --with-form strips `.form()` helpers and breaks ProductForm / other `*.form()` callers.
