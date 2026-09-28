---
paths:
  - 'app/Http/Controllers/Customer/OrderController.php,resources/js/components/customer/order-list.tsx'
  - 'app/Http/Controllers/Customer/OrderController.php,routes/customer.php,resources/js/components/customer/order-list.tsx'
---

# Components Customer

## Customer order list has no product image
The customer dashboard order list shows order number, product name chips, total, and status only. Do not send or render a first-item product image — one photo cannot represent a multi-product order.

## Customer order URLs use order number
GET /orders/{order:order_number} binds on orders.order_number. List links and View order use that public number. Do not put order.id in customer order list or detail payloads or in the address bar.
