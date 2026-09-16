---
paths:
  - 'resources/js/components/stock/**'
---

# Stock

## Stock pair pickers use paginated comboboxes
Manage stock, Update price, and Add discount share StockPairFields. Branch and product selects are InfiniteScrollCombobox against stock/branch-options and stock/product-options (20 per page, filter[search], append on scroll)—never map context.branch_options or product_options into those pickers. Omit noneLabel so a pair is required; Stock transactions still passes All branches/products. Platform pages pass both option URLs; branch pages pass productOptionsUrl only because the branch is locked.

## Manage stock is a full-width workspace
Manage stock is a full-width catalog workspace (`flex w-full flex-col gap-4 p-2 md:p-4`), not a centered max-w form. Match ProductForm: details left (`xl:grid-cols-[minmax(0,1fr)_minmax(17.5rem,20rem)]`) and a snapshot rail of DemoStatCards (stock, branch, product) on the right.
