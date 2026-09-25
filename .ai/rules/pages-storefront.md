---
paths:
  - resources/js/pages/storefront/category.tsx
---

# Pages Storefront

## Category pages have no branch picker
/shop/{category} toolbar is search + sort only (sort lg:w-56). Do not add StorefrontBranchSelect or branches/branchId props. Search and sort use the same Inertia query keys as shop (search, sort).
