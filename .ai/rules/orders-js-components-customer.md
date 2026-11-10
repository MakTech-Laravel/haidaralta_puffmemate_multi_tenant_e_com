---
paths:
  - 'app/Http/Controllers/Customer/OrderController.php,resources/js/pages/customer/orders/show.tsx,resources/js/components/customer/order-track-*.tsx'
---

# Orders Js Components Customer

## Customer order detail shows item images
Order detail products include image from product_snapshot via MediaUrl::resolve. Render thumbnails with StorefrontProductImage in a scrollable Items list (max-h-72), plus subtotal/delivery/total. Keep the list image-free; detail only. Sidebar (timeline/driver) is h-fit sticky; map sits full-width under the grid so long carts do not leave a tall empty right column. Soft-removed late lines (`isRemoved`) stay in the list with a Removed label; do not hide them.

## Customer order detail shows item images
Order detail products include image from product_snapshot via MediaUrl::resolve. Render thumbnails with StorefrontProductImage in a scrollable Items list (max-h-72), plus subtotal/delivery/total. Keep the list image-free; detail only. Sidebar (timeline/driver) is h-fit sticky. Do not show a map placeholder on customer order detail.
