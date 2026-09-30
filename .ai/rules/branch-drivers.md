---
paths:
  - 'app/Services/Driver/**,app/Http/Controllers/Branch/DriverProductAssignController.php,resources/js/pages/branch/assign-products.tsx'
---

# Branch Drivers

## Branch Assign Product moves stock to drivers
Branch Driver management Assign Product (GET/POST /branch/{branch}/assign-products) assigns identical per-line qty to each selected driver. Multiple mode costs drivers×qty from branch inventory via InventoryTransactionType::DriverAssign and upserts driver_inventories. Only active drivers assigned to this branch and this branch’s sellable stock appear. Toggle single↔multiple clears driver selection. Do not restore a branch.drivers.assign route for reassigning existing drivers across branches.
