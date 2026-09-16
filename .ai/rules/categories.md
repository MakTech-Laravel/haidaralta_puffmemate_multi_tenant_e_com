---
paths:
  - 'resources/js/pages/**/catalog/categories/index.tsx'
---

# Categories

## Category list View products filters product index
Platform and branch category indexes expose Catalog → View products (Package icon) linking to ProductController.index with query filter[category_id]=category.id (scalar or array). Product indexes use CategoryFilterCombobox (InfiniteScrollMultiCombobox → categories/options), not a Select of filterOptions.categories. Do not invent a separate products-by-category route.
