---
paths:
    - resources/js/lib/fcm.ts
---

# Lib

## FCM token-subscribe-failed

token-subscribe-failed / missing OAuth on getToken usually means browser lost Firebase Installations auth: API key HTTP-referrer restrictions (allow localhost:8000), missing FCM Registration + Installations APIs on the key, mixed localhost vs 127.0.0.1 origins, or stale firebase-*-database IndexedDB. Client clears IDB and re-registers SW once on that error.
