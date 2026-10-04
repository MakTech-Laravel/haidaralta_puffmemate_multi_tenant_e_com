---
paths:
  - 'app/Http/Controllers/Platform/OrderController.php,resources/js/pages/platform/orders/show.tsx'
---

# Pages Platform Orders

## Platform order detail is a premium ops view
Platform order show uses statusLabel/paymentStatusLabel (never raw enums), item thumbnails via MediaUrl + StorefrontProductImage in a scrollable list, sticky sidebar (address/totals/payment/history), and a hero header with customer + placedAt. Timeline labels come from OrderStatus/FulfillmentStatus::label(). Keep all fulfillment ops (status, assign, transfers, cancel/retry/return) on the page.
