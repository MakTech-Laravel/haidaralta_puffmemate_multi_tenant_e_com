---
paths:
  - 'database/seeders/**'
---

# Seeders

## Dev seed accounts use email as password
UserSeeder constants (PLATFORM_SUPER_ADMIN, BRANCH_1_*, DRIVER, CUSTOMER, etc.) are the canonical @dev.com login emails. Password for each seeded user equals their email. Branch 1 = downtown, branch 2 = uptown. Prefer UserSeeder::* constants in tests over hard-coded strings.
