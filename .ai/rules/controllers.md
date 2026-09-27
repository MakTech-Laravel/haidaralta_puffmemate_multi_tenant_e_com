---
paths:
    - app/Http/Controllers/FirebaseMessagingServiceWorkerController.php
---

# Controllers

## FCM SW double notification

Server sends withNotification(); Chrome auto-displays that in background. SW onBackgroundMessage must NOT call showNotification when payload.notification is present — only show for data-only messages. Foreground uses onMessage → Sonner.

## FCM SW showNotification env

SW showNotification is gated by FCM_SW_SHOW_NOTIFICATION (config services.firebase.sw_show_notification). Default false avoids duplicate OS toasts when server sends withNotification(); set true to force SW display.

## FCM SW is also the PWA worker
PWA installability uses this same FCM worker at scope /. Do not add a second SW. Register it on every app boot (permission is unrelated). No fetch cache. notificationclick + skipWaiting belong here. PWA_SW_ENABLED false serves an unregistering kill-switch.

## SW click unwraps FCM_MSG and defaults home
notificationclick reads data.url, data.link, or data.FCM_MSG.data.url. Empty means origin + /. Same-origin: focus then navigate, else openWindow. Other origin: openWindow only. Do not showNotification when the FCM payload already has notification.
