---
paths:
  - 'resources/js/components/customer/product-to-review-*.tsx'
  - resources/js/components/customer/write-product-review-form.tsx
---

# Js Components Customer

## Review list shows order number as Order ID
Product To Review columns are Products, Order ID (order_number), Shipping Status, Action. Paginate on the server like customer orders. Row Write a Review uses the order item id.

## Reviewed rows show View Review
Product To Review Action is Write a Review until a review exists for that order+product, then View Review. View Review links to storefront products.show?tab=reviews, not the write page. Write a Review still uses the order item id.

## Write review form posts to store
WriteProductReviewForm posts rating and body with Inertia useForm to customer.product-to-review.store via Wayfinder. Do not keep a local-only submit that clears the fields.
