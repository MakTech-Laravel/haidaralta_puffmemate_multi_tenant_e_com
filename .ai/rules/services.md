---
paths:
    - app/Services/OtpService.php
---

# Services

## Use OtpService for all OTP issue and verify

All OTP flows go through OtpService (not ad-hoc tables or caches). Pass optional subject Model when the OTP belongs to an entity; omit for phone-only auth OTPs. Channel sending stays on SendsSms.
