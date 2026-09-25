---
paths:
  - 'app/Services/Notifications/**'
---

# Notifications

## In-app notifications are one parent plus per-user rows
Persist every in-app send as one notifications row and one user_notifications row per recipient. Features must call NotificationService or notify_users(); do not insert these rows from feature code. Do not use Laravel's default morph notifications table for this inbox.
