---
paths:
  - 'app/Http/Controllers/Platform/ProductReviewController.php, resources/js/pages/platform/reviews/**'
  - 'app/Http/Controllers/Branch/ProductReviewController.php, app/DataTables/BranchProductReviewDataTable.php, resources/js/pages/branch/reviews/**, resources/js/components/branch/nav.ts'
---

# Reviews

## Platform review details show page
Platform review details live at GET /platform/reviews/{id} (platform.reviews.show). Show UI mirrors contact/wholesale inbox: Heading, Visibility badge, Hide/Unhide + Back, meta strip with stars, Customer/Product cards, Message/Bubble for full body. List Details links to show. Do not expose hidden_at as a date field.

## Branch reviews
Branch list/show under Customer management (`manage-branch-orders`). Scope reviews with `whereHas('order.fulfillment', branch_id)`. No branch filter on the list. Hide/Unhide only for in-scope reviews. Customer/product/order links use branch portal routes.
