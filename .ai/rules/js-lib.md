---
paths:
  - 'resources/js/lib/pwa.ts, resources/js/lib/fcm.ts'
---

# Js Lib

## Unregister foreign service workers then register FCM
On boot unregister any SW whose scriptURL is not firebase-messaging-sw.js, then register ours. Do not wait for notification permission.
