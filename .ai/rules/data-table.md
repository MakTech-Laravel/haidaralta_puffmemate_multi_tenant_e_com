---
paths:
  - 'app/DataTables/**, resources/js/components/data-table/**'
---

# Data Table

## Price history DataTable mirrors the stock ledger
BranchProductPriceHistoryDataTable filterOptions carry sources plus the currently selected branch/product only. Platform columns are Product (name + SKU + variant), then Branch, then source/changes/when/by. Product and branch pickers load pages from stock/*-options JSON. Branch portal omits the branch column, branch filter, and branch text search. The page is */reports/price-history.
