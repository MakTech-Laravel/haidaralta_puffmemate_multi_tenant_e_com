---
paths:
  - 'app/Http/Controllers/Platform/ProductReviewController.php, app/DataTables/ProductReviewDataTable.php, resources/js/pages/platform/reviews/**, resources/js/components/platform/nav.ts'
---

# Components Platform

## Platform Reviews list moderation
Platform Customer management → Reviews is /platform/reviews under manage-users. List uses ProductReviewDataTable (search + user_id/product_id/branch_id/order_number filters; selected_* only in filterOptions). Moderation is product_reviews.hidden_at (not a list column); Created at is review created_at. StorefrontCatalog excludes whereNull(hidden_at). Details show page is deferred — Details row action stays disabled.
