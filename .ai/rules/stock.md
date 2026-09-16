---
paths:
  - 'resources/js/components/stock/**'
---

# Stock

## Stock pair pickers use paginated comboboxes
Manage stock, Update price, and Add discount share StockPairFields. Branch and product selects are InfiniteScrollCombobox against stock/branch-options and stock/product-options (20 per page, filter[search], append on scroll)—never map context.branch_options or product_options into those pickers. Omit noneLabel so a pair is required; Stock transactions still passes All branches/products. Platform pages pass both option URLs; branch pages pass productOptionsUrl only because the branch is locked.

## Manage stock is a full-width workspace
Manage stock is a full-width catalog workspace (`flex w-full flex-col gap-4 p-2 md:p-4`), not a centered max-w form. Match ProductForm: details left (`xl:grid-cols-[minmax(0,1fr)_minmax(17.5rem,20rem)]`) and a snapshot rail of DemoStatCards (stock, branch, product) on the right.

## Manage stock quantity is a clearable string
Manage stock quantity is stored as a digit string (default "0"), not Number(). Empty string is allowed while typing; convert with parseInt only in form.transform on submit. Do not Number(event.target.value) on change — that snaps cleared fields back to 0.

## Stock quantity is unsigned digits
Quantity fields accept digits only (empty while clearing, then 0–n). Type chooses direction: New entry adds, Reduce subtracts, Adjustment sets counted on-hand and shows a live variance preview. Reason select is required only for Adjustment. Disable Save when counted is empty or matches current stock. Ledger may display a computed negative Adjustment variance; users never type a minus sign.

## Adjustment reason sits beside quantity
On Adjustment, Reason sits beside Counted quantity. Other reveals a required reason_note input. New entry and Reduce have no reason or notes.

## Other reason is a full-width counted textarea
Reason SelectTrigger must match the quantity stepper (h-12 min-h-12 rounded-xl data-[size=default]:h-12). Other uses AdjustmentReasonNote: full-width textarea after the quantity/reason row, live n/255 count, maxLength 255.

## Import stock uses multi infinite pickers
Import stock is New entry only. Branch and product pickers are InfiniteScrollMultiCombobox against stock/*-options (same as Manage stock, multi-select). Do not map context.branch_options or product_options into checkboxes. Quantity is an unsigned digit string with the manage-stock stepper. Branch portal locks the current branch.
