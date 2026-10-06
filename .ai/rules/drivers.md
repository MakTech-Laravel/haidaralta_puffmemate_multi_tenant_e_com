---
paths:
  - 'app/Http/Controllers/Branch/*Driver*.php,resources/js/pages/branch/drivers/**'
---

# Drivers

## Branch portal cannot assign existing drivers
Branch portal cannot assign existing drivers. No assignableDrivers prop, no branch.drivers.assign route. Branches only create drivers (auto-assigned to this branch). Platform alone assigns/transfers drivers across branches (including unassigned). Branch lists stay scoped to this branch's assignments.

## Branch Assign Product is driver stock not people assign
GET/POST branch.assign-products moves this branch’s inventory onto selected drivers (driver_inventories + DriverAssign ledger). Multiple mode gives each driver the same qty and deducts drivers×qty. Do not confuse with cross-branch people assignment.
