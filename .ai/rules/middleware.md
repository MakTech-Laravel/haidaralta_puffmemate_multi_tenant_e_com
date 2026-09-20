---
paths:
  - 'routes/public.php,bootstrap/app.php,app/Http/Middleware/EnsurePortalGuard.php'
---

# Middleware

## All portals share /login
There is one login page at /login for every portal. redirectGuestsTo and EnsurePortalGuard must use route('login'). Prefixed guesses /driver/login, /platform/login, /branch/login, and /customer/login redirect to /login. Do not add portal-specific login routes.
