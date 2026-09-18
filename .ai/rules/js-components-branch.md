---
paths:
  - 'resources/js/components/branch/**, resources/js/components/branch/product-inventory-panel.tsx'
---

# Js Components Branch

## Inventory panel View price history
Do not render last-N price history rows under the Inventory & pricing card. **View price history** (outline) goes to reports/price-history with the same product + branch query (locked omitted; no variant). Pass priceHistoryUrl into the panel alongside transactionsUrl.
