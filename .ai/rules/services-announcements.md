---
paths:
  - 'app/Services/Announcements/**'
---

# Services Announcements

## Announcement send is System only via notify_users
Compose POST creates an announcements row, resolves group/individual recipients, then calls notify_users(). System (in-app inbox) is the only live channel. Persist via_phone/via_email/via_push but do not SMS, mail, or FCM. Do not insert notifications/user_notifications by hand.
