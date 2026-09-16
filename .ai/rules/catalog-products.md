---
paths:
  - 'resources/js/pages/**/catalog/products/index.tsx'
---

# Catalog Products

## Product category filter uses paginated multi combobox
Product list category filters use CategoryFilterCombobox → InfiniteScrollMultiCombobox against categories/options (20/page, filter[search]). filter[category_id] accepts one id or an array (OR match). filterOptions.categories carries only the currently selected rows for trigger labels—never the full catalog. Toolbar filters pass showSelectedBadges={false}; form pickers keep chips.
