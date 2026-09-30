---
paths:
  - 'app/Services/Driver/**,app/Http/Controllers/Branch/DriverProductAssignController.php,resources/js/pages/branch/drivers/assign-products.tsx'
---

# Pages Branch Drivers

## Assign Product writes per-driver inventory ledger
DriverStockAssignmentService still deducts branch stock once per product (qty×drivers) via InventoryTransactionType::DriverAssign, and also writes driver_inventory_transactions one row per driver × product with driver before/after. Reason stays null. History UI is Branch Reports → Product assign transactions.
