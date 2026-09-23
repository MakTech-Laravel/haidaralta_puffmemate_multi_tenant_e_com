---
paths:
  - 'resources/js/components/stock/**,app/Support/StockPageContext.php,app/Support/ProductManageRedirect.php'
---

# Stock Support Support

## No locked stock picker query
Stock manage/import deep links use only ?branch=&product= — no locked query. Pickers stay editable. Branch portal hides Branch via lockBranch only (not LockedValue). Invalid product/branch ids still 404 via normal resolve.
