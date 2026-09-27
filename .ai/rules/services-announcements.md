---
paths:
  - 'app/Services/Announcements/**'
---

# Services Announcements

## Announcement send is System only via notify_users
Compose POST creates an announcements row, resolves group/individual recipients, then calls notify_users(). System (in-app inbox) is the only live channel. Persist via_phone/via_email/via_push but do not SMS, mail, or FCM. Do not insert notifications/user_notifications by hand.

## Announcement push uses SendFcmPushJob
System still goes through notify_users(). When via_push is true, also dispatch SendFcmPushJob afterCommit with the same recipient ids. FCM data.url is the compose link for SW notificationclick. Phone and Email remain stored flags only. Do not send to every fcm_device_tokens row.
