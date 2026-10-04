---
paths:
  - 'resources/js/pages/platform/wholesale-inquiries/place-order.tsx,resources/js/components/platform/wholesale/**'
---

# Platform Wholesale

## Place-order address uses suggest and map pin
Platform wholesale place-order customer address uses AddressSuggestInput + CheckoutMapDialog like storefront wholesale. Free text is query-only; address/lat/lng commit only on suggestion or map select. canPlace requires latitude and longitude.
