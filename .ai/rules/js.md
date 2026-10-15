---
paths:
  - 'resources/js/{app.tsx,components/navigation-overlay.tsx}'
---

# Js

## Navigation overlay replaces progress bar
Page visits use NavigationOverlay. createInertiaApp progress is false. Track `visit.id` from `before`; end on the first `navigate`/`success`/`error`/`finish` for that id (plus `httpException`/`networkError`). Cached same-URL clicks fire only `before` + `success`. Ignore non-GET (post/put/patch/delete mutations show their own button/form processing state instead), async, prefetch, `showProgress: false`, and partial (`only`/`except`) visits. Inertia sets `showProgress` false when `async` is true. Mirror Inertia's bar: reveal only if still pending after 250ms. The moment the visit ends, start the CSS fade-out — no extra hold. Block clicks while entering/visible (full-screen overlay looks modal; add-to-cart and other actions must not fire underneath). `pointer-events-none` only during fade-out so the new page is usable immediately. Fade in/out is `duration-300`; unmount on `transitionend` (350ms fallback). `prefers-reduced-motion` skips fade and truck motion. 60s failsafe only if the visit never ends.
