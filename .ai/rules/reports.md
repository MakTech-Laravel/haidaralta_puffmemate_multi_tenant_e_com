---
paths:
  - 'app/Http/Controllers/{Platform,Branch}/Reports/**'
  - 'resources/js/pages/{platform,branch}/reports/**'
---

# Reports

## Stock transactions live under Reports
Stock transactions is a report, not a Product management child. Live URL is reports/stock-transactions (controllers App\Http\Controllers\{Platform,Branch}\Reports\StockTransactionController, pages */reports/stock-transactions). Do not keep a stock/transactions redirect. Sidebar: Reports → Stock transactions; platform Branch management holds Manage stock; Product management holds Categories, Products, and Bulk Import when show_bulk_import. Permission stays manage-branch-inventories / manage-branch-products.
