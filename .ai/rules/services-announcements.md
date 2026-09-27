---
paths:
  - 'app/Services/Announcements/**'
---

# Services Announcements

## Announcement send is System only via notify_users
Compose POST creates an announcements row, resolves group/individual recipients, then calls notify_users(). System (in-app inbox) is the only live channel. Persist via_phone/via_email/via_push but do not SMS, mail, or FCM. Do not insert notifications/user_notifications by hand.

## Announcement push uses SendFcmPushJob
System still goes through notify_users(). When via_push is true, also dispatch SendFcmPushJob afterCommit with the same recipient ids. FCM data.url is the compose link for SW notificationclick. Phone and Email remain stored flags only. Do not send to every fcm_device_tokens row.

## Announcement phone logs and email is address-only
When via_phone is true, dispatch SendAnnouncementPhoneJob afterCommit; it renders sms.announcements.sent and sends through SendsSms (LogSmsSender outside tests). When via_email is true, dispatch SendAnnouncementEmailJob afterCommit; AnnouncementMail goes only to recipients with a non-empty email. Same userIds as System/Push. Phone and Email are no longer flags-only.

## Dispatch one email job per address
When via_email is true, query recipients with a non-empty email (ordered by id) and dispatch SendAnnouncementEmailJob afterCommit once per user. Delay each job after the first by index * 2 seconds. Do not dispatch one job with a userIds list.
