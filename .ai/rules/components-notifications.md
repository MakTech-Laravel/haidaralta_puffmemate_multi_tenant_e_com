---
paths:
  - 'resources/js/components/notifications/**'
---

# Components Notifications

## Announcement links render as a button
InboxService maps data.link_url to href and data.link_label to linkLabel. NotificationDetail and NotificationList show a button (label or Open) that navigates to href. Do not reuse href as the inbox show URL — that stays on getItemHref(). Skip a nested button in the bell popover.
