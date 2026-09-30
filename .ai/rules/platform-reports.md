---
paths:
  - 'app/Http/Controllers/Platform/DriverProductAssignController.php,app/Http/Controllers/Platform/Reports/ProductAssignTransactionController.php,resources/js/pages/platform/drivers/assign-products.tsx,resources/js/pages/platform/reports/product-assign-transactions.tsx'
---

# Platform Reports

## Platform Assign Product is branch-first
Platform Assign Product (GET/POST /platform/assign-products, manage-drivers) picks a branch first (?branch=), then only that branch’s sellable stock and active drivers can be assigned via DriverStockAssignmentService. Reports → Product assign transactions (same permission) reads driver_inventory_transactions across branches with Branch column + filter[branch_id]; option endpoints live on the report controller so manage-drivers alone is enough. Nav: Assign Product under Driver management after Drivers; report replaces Reports Drivers soon.
