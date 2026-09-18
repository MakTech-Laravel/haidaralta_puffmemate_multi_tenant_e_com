---
paths:
    - 'resources/js/pages/**/catalog/products/index.tsx'
    - 'resources/js/pages/**/catalog/products/{index,show}.tsx'
---

# Catalog Products

## Product category filter uses paginated multi combobox

Product list category filters use CategoryFilterCombobox → InfiniteScrollMultiCombobox against categories/options (20/page, filter[search]). filter[category_id] accepts one id or an array (OR match). filterOptions.categories carries only the currently selected rows for trigger labels—never the full catalog. Toolbar filters pass showSelectedBadges={false}; form pickers keep chips.

## Branch product rows include View

Branch products index Catalog group is View / Edit. Stock group is Manage stock / Stock transactions / Price history. View is `branch.products.show` (listing details, no Branch network). Manage stock stays locked `stock/manage` with the current branch + product.

## Platform product rows include Manage stock

Platform products index Catalog group is View / Manage branches / Edit. Stock group is Manage stock / Stock transactions / Price history. **Manage stock** opens `stock/manage?product=&locked=product` (product selected; pick a branch). **Manage branches** stays the product-centric branch list.

## Platform branch product rows use grouped actions

Platform `{branch} products` (`platform.branches.products.index`) uses labeled `DataTableRowActions` groups, not a flat `items` list. Catalog: View (`platform.products.show`). Stock: Manage stock (`locked=both`) / Stock transactions / Price history.

## Catalog Manage stock preselects product

Catalog Manage stock with only a product (platform details header + products index) opens stock/manage?product={id}&locked=product so the product picker is pre-selected. Pair-known Manage stock stays locked=both. Keep Manage branches as the product-centric branch list.
