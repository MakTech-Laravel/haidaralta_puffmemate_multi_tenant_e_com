---
paths:
  - 'resources/js/{app.tsx,components/navigation-overlay.tsx}'
---

# Js

## Navigation overlay replaces progress bar
Page visits use NavigationOverlay. createInertiaApp progress is false. Show on router `before` and remember `visit.id`; hide on the first `navigate`/`success`/`error`/`finish` carrying that id (plus `httpException`/`networkError`). Never rely on `start`, and never on `navigate`/`finish` alone: a cached visit fires no `start`/`finish`, and a visit to the current URL replaces history so it fires no `navigate` — a cached same-URL click fires only `before` + `success`. Ignore async, prefetch and partial (`only`/`except`) visits so cart qty/add and debounced search stay quiet. Mirror Inertia's progress bar: reveal only if the visit is still pending after 250ms (cached/prefetched visits show nothing), then keep it at least 300ms once shown to avoid flicker. No artificial minimum when it never appeared.
