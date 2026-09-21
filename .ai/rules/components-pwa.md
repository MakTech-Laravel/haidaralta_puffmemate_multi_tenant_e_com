---
paths:
  - 'resources/js/app.tsx, resources/js/components/pwa/**'
---

# Components Pwa

## withApp wrappers must not call usePage
createInertiaApp withApp wraps outside Inertia's page context. Do not call usePage() (or any Inertia hook) from withApp providers such as PwaInstallProvider — pass page.props in. usePage is only valid inside {app} / layouts / pages.
