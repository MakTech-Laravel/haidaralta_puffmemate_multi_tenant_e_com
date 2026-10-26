---
paths:
  - 'app/Http/Controllers/Branch/OrderController.php,resources/js/pages/branch/orders/show.tsx'
---

# Pages Branch Orders

## Branch order detail matches the Platform visual contract
Branch order show mirrors Platform: hero (fulfillment number, status/payment/source badges via `orderStatusBadgeClass` / `paymentStatusBadgeClass` + `statusLabel` / `paymentStatusLabel`, order total), facts row (customer, placedAt, payment method label — never raw `cod`), fulfillment card with item thumbnails (`StorefrontProductImage`), sticky sidebar (delivery details, order summary, then Delivery attempts when eligible). Shared section helpers live under `components/orders/order-detail-section`. Keep branch-only ops on the page: assign driver with advance statuses (Assign / picked up / on the way), transfer receive, distance, delivery date/slot, late-item Remove. No platform wholesale inquiry or customer deep-link. While `canReturn` (DeliveryFailed), the latest can't-deliver reason/note sits under the return action — not in the sidebar. Beside return actions (`canReturn` / `canConfirmReturn`), show the attempt count (e.g. `1 attempt` / `2 attempts`). After Start return, Delivery attempts (via `fulfillment.deliveryAttempts`) appear in the sticky sidebar under Order summary.
