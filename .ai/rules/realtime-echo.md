# Realtime / Echo client

- Private channel: `App.Models.User.{id}`; private test event: `.realtime.test` (leading dot = broadcastAs).
- Sonner toasts for that event come from `usePrivateRealtimeToast` (mounted in `sonner.tsx`). Do not reconfigure Reverb for toast UX.
- Page cleanup must only `stopListening` on the private user channel — never `echo.leave()` it — so the global toast subscription stays alive.
- Echo is browser-only: `createEcho` / top-level `getEcho()` in `app.tsx` must not run during Inertia/Vite SSR (Pusher is not available in Node).
- `usePrivateRealtimeToast` must use `router.page` / router events, not `usePage` — Toaster lives in `withApp` outside the page context.
