---
paths:
    - 'resources/js/components/stock/**, resources/js/components/stock/manage-stock-page.tsx'
---

# Components Stock

## Manage stock links to the price history report

Manage, Import, Stock transactions, and Price history share the query contract product, branch (no locked). Manage’s View price history (outline, next to View transactions) goes to reports/price-history with the same product + branch query (no variant). Pass priceHistoryUrl into ProductInventoryPanel. Do not embed the history table on Manage.
