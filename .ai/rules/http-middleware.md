---
paths:
  - 'resources/js/layouts/branch/**,resources/js/components/portal/portal-brand.tsx,app/Http/Middleware/HandleInertiaRequests.php'
---

# Http Middleware

## Branch sidebar mark uses branch logo
Branch portal shares branch.logo_url on Inertia props and passes it as PortalShellLayout brandLogoUrl → PortalBrand → AppBrandMark so the sidebar mark is the branch logo (not the application tile).
