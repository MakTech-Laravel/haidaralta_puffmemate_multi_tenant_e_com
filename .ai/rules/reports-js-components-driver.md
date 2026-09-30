---
paths:
  - 'app/Services/Driver/**,app/Http/Controllers/Branch/Reports/ProductAssignTransactionController.php,resources/js/pages/branch/reports/product-assign-transactions.tsx,resources/js/components/driver/product-assign-transactions-page.tsx'
---

# Reports Js Components Driver

## Product assign transactions are per-driver ledger rows
Branch Reports → Product assign transactions (GET/export reports/product-assign-transactions, manage-branch-drivers) reads driver_inventory_transactions. Assign Product writes one ledger row per driver × product with driver inventory before/after; Reason stays null. Branch inventory_transactions DriverAssign stays one aggregate row per product for Stock transactions. Multi-driver assign therefore yields N history rows. Nav under Reports and history; product filter options use the report’s own product-options route.
