---
paths:
  - 'app/Http/Controllers/Branch/OrderController.php,resources/js/pages/branch/orders/show.tsx'
---

# Pages Branch Orders

## Branch order detail matches the Platform visual contract
Branch order show mirrors Platform: hero (fulfillment number, status/payment/source badges via `orderStatusBadgeClass` / `paymentStatusBadgeClass` + `statusLabel` / `paymentStatusLabel`, order total), facts row (customer, placedAt, payment method label — never raw `cod`), fulfillment card with item thumbnails (`StorefrontProductImage`), sticky sidebar (delivery details + order summary). Shared section helpers live under `components/orders/order-detail-section`. Keep branch-only ops on the page: assign driver with advance statuses (Assign / picked up / on the way), transfer receive, distance, delivery date/slot. No platform wholesale inquiry or customer deep-link.
