# Realtime / Echo client

- Private channel: `App.Models.User.{id}`; private test event: `.realtime.test` (leading dot = broadcastAs).
- Platform backups: private `platform.database-backups` (permission `manage-database-backups`); event `.database-backup.history-updated` → page partial reload.
- Sonner toasts for that event: `showRealtimeTestToast` / `usePrivateRealtimeToast` via `AppToaster` in `app.tsx` — never wire hooks into `components/ui/sonner.tsx` (keep the shadcn UI wrapper clean).
- Page cleanup must only `stopListening` on the private user channel — never `echo.leave()` it — so the global toast subscription stays alive. Page-scoped channels (e.g. backups) should `leave()` on unmount.
- Echo is browser-only: `createEcho` / top-level `getEcho()` in `app.tsx` must not run during Inertia/Vite SSR (Pusher is not available in Node).
- Inertia v3 has no `router.page`. Seed `authUserId` from `withApp`'s `page` into `AppToaster`, and sync via `navigate`/`success` `detail.page` (not `usePage`).
- Do not `pnpm dlx shadcn add sonner` without adapting afterward: registry sonner uses `next-themes`; this app uses `useAppearance`. After add, swap `useTheme()` → `useAppearance()` and remove `next-themes` if it was installed. Keep toast hooks in `AppToaster`, not in `ui/sonner.tsx`.
