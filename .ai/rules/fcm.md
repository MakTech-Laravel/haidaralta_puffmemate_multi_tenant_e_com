---
paths:
  - 'app/Services/Fcm/**'
---

# Fcm

## FCM reusable send layer and token lifecycle
Send FCM only via FcmPushService / fcm_notify_user(s) / SendFcmPushJob — never Kreait Messaging from controllers. Store tokens in fcm_device_tokens with upsert by token. Register post-auth from the client (permission granted or Enable CTA), not on login form submit. Admin JSON: FIREBASE_CREDENTIALS path; web config: VITE_FIREBASE_*. SW served at /firebase-messaging-sw.js.
