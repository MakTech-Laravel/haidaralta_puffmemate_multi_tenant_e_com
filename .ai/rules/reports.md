---
paths:
  - 'app/Http/Controllers/{Platform,Branch}/Reports/**'
  - 'resources/js/pages/{platform,branch}/reports/**'
---

# Reports

## Stock transactions live under Reports
Stock transactions is a report, not a Product management child. Live URL is reports/stock-transactions (controllers App\Http\Controllers\{Platform,Branch}\Reports\StockTransactionController, pages */reports/stock-transactions). Do not keep a stock/transactions redirect. Sidebar: Reports → Stock transactions then Price history; platform Branch management holds Manage stock; Product management holds Categories, Products, and Bulk Import when show_bulk_import. Permission stays manage-branch-inventories / manage-branch-products. Ledger Buying is inventory_transactions.unit_cost (em dash when null).

## Price history lives under Reports
Price history is a report, not an inline Manage stock card. Live URL is reports/price-history (controllers App\Http\Controllers\{Platform,Branch}\Reports\PriceHistoryController, pages */reports/price-history). Product then Branch comboboxes first, plus source/from/to and Spatie pagination. Do not restore an inline last-N history list on Manage.

## Report export GETs live next to indexes
Stock transaction and price history spreadsheet exports are GET routes next to the report indexes: reports/stock-transactions/export and reports/price-history/export (mirrored in routes/branch.php). Same permission middleware as the list. Query uses Spatie filter[] plus format=csv|xlsx and scope=all|period|filters|page. No precognitive. Filenames are {report}-{scope}-{Y-m-d}.{csv|xlsx}. Empty results still download a header-only file.
