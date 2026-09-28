---
paths:
  - 'app/Services/Customer/**'
---

# Services Customer

## Product to review is delivered order lines
ProductToReviewService lists OrderItems on the customer's OrderStatus::Delivered orders only. Payload id is order_items.id; orderId is orders.order_number never orders.id. Do not load fulfillments. Do not use DemoCatalog.

## Reviews unique per order and product
product_reviews is unique on (order_id, product_id), not order_items.id. List payload id stays order_items.id; orderId is order_number never orders.id. Add reviewed plus viewUrl to products.show?tab=reviews when that pair already has a review. Do not load fulfillments.
