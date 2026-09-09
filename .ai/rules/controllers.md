---
paths:
  - app/Http/Controllers/FirebaseMessagingServiceWorkerController.php
---

# Controllers

## FCM SW double notification
Server sends withNotification(); Chrome auto-displays that in background. SW onBackgroundMessage must NOT call showNotification when payload.notification is present — only show for data-only messages. Foreground uses onMessage → Sonner.

## FCM SW showNotification env
SW showNotification is gated by FCM_SW_SHOW_NOTIFICATION (config services.firebase.sw_show_notification). Default false avoids duplicate OS toasts when server sends withNotification(); set true to force SW display.
