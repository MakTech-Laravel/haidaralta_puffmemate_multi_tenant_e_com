---
paths:
  - 'app/Http/Controllers/Branch/*Driver*.php,resources/js/pages/branch/drivers/**'
---

# Drivers

## Branch portal cannot assign existing drivers
Branch portal cannot assign existing drivers. No assignableDrivers prop, no branch.drivers.assign route. Branches only create drivers (auto-assigned to this branch). Platform alone assigns/transfers drivers across branches (including unassigned). Branch lists stay scoped to this branch's assignments.
