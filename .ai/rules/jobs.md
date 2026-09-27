---
paths:
  - app/Jobs/SendAnnouncementEmailJob.php
---

# Jobs

## Announcement mail sends inside one job
SendAnnouncementEmailJob is the only queued unit. Mail::send() AnnouncementMail synchronously (no ShouldQueue on the mailable). After the first send, sleep(1) between recipients except in testing, so Mailtrap Testing does not 550 rate-limit. Job timeout is 300.

## Announcement mail waits 2s after each send
After each Mail::send(), wait 2 seconds before the next recipient (skipped in testing). Sleep happens after SMTP accepts the message, not before the first send, so Mailtrap Testing does not 550 rate-limit.

## Announcement email is one job per user
SendAnnouncementEmailJob sends one AnnouncementMail via Mail::send() for a single userId. Do not loop recipients or sleep inside the job. Stagger dispatch from AnnouncementService with delay(index * 2) seconds so Mailtrap is not flooded. AnnouncementMail stays synchronous (no ShouldQueue).
