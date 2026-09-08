# Realtime / Echo client

- Private channel: `App.Models.User.{id}`; private test event: `.realtime.test` (leading dot = broadcastAs).
- Sonner toasts for that event come from `usePrivateRealtimeToast` (mounted in `sonner.tsx`). Do not reconfigure Reverb for toast UX.
- Page cleanup must only `stopListening` on the private user channel — never `echo.leave()` it — so the global toast subscription stays alive.
