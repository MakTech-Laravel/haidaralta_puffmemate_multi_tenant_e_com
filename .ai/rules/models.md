---
paths:
  - 'app/Models/**.php'
---

# Models

## Scout timestamps while timestamps=false

SessionGuard remember-token updates set `$model->timestamps = false` before save. Scout’s ModelObserver then calls `toSearchableArray()` while Eloquent date casting for `created_at`/`updated_at` is off, so those attributes are strings. Always normalize via `App\Support\SearchableTimestamp::from()` (Carbon::parse-safe)—never `$this->created_at?->getTimestamp()` alone.

## OTP storage is otp_records
Multi-purpose OTPs live in otp_records via OtpRecord + OtpPurpose + OtpService. phone is the SMS destination. Bind feature entities (orders, etc.) with optional morph subject (subject_type/subject_id); auth flows leave subject null. Scope issue/verify by phone + purpose + subject. Add new OtpPurpose cases when features ship — do not create separate OTP tables.
