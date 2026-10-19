---
paths:
  - 'app/Http/Controllers/Platform/OrderController.php,resources/js/pages/platform/orders/show.tsx'
---

# Pages Platform Orders

## Platform order detail is a premium ops view
Platform order show uses statusLabel/paymentStatusLabel (never raw enums) with order-badges colors, item thumbnails via MediaUrl + StorefrontProductImage in a scrollable list, and a hero header (order total, status/payment/source badges, customer link, placedAt, branch, payment method label — never raw `cod`). Each fulfillment card keeps its own actions footer. History sits in the main column; the sticky sidebar holds only short cards (delivery details, order summary with payment) so it never overflows the viewport. Driver-sale orders without a street address show an explicit empty note. Timeline labels come from OrderStatus/FulfillmentStatus::label(). Keep all fulfillment ops (status, assign, transfers, cancel/retry/return) on the page. Shared section helpers (`SectionCard`, `HeaderFact`, `SummaryRow`) live under `components/orders/order-detail-section`.
