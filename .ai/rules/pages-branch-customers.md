---
paths:
  - 'app/Http/Controllers/Branch/CustomerController.php,app/DataTables/BranchCustomerDataTable.php,resources/js/pages/branch/customers/**,resources/js/components/branch/nav.ts'
---

# Pages Branch Customers

## Branch customers are read-only and fulfillment-scoped
Branch Customers under `manage-branch-orders` is list + show only (no create/edit/toggle). Scope customers with at least one `order_fulfillments.branch_id` for the current branch — never `users.branch_id`. Show recent orders for this branch and link via fulfillment id to `branch.orders.show`.
