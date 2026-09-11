---
paths:
    - 'database/seeders/**'
---

# Seeders

## Dev seed accounts use phone login

UserSeeder phone constants (`PLATFORM_SUPER_ADMIN_PHONE`, `BRANCH_1_*_PHONE`, `DRIVER_PHONE`, `CUSTOMER_PHONE`, etc.) are the canonical E.164 login identifiers. Shared password for every seeded user is `password` (`UserSeeder::DEFAULT_PASSWORD`). Optional `@dev.com` email constants remain for display only. Branch 1 = downtown, branch 2 = uptown. Prefer `UserSeeder::*_PHONE` in tests for authentication; use email constants only when asserting display email.
