---
paths:
  - 'app/Services/Workforce/**,app/Http/Controllers/{Platform,Branch}/**'
---

# Controllers Platform Branch

## Never convert UserType on transfer
Transfers never change users.user_type. Platform and customer users cannot transfer or be assigned shifts. Drivers only transfer_type=branch_change (driver_branch_assignments). Branch staff only move between branches and/or Spatie roles (branch_change or role_change). Combined staff branch+role is one branch_change history row. v1 staff transfers are platform-only (no branch.staff.transfer).
