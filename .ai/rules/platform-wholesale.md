---
paths:
  - 'resources/js/pages/platform/wholesale-inquiries/place-order.tsx,resources/js/components/platform/wholesale/**'
---

# Platform Wholesale

## Place-order address uses suggest and map pin

Platform wholesale place-order customer address uses AddressSuggestInput + CheckoutMapDialog like storefront wholesale. Free text is query-only; address/lat/lng commit only on suggestion or map select. canPlace requires latitude and longitude.

## Reuse shared order UI

Payment choice uses components/orders/payment-method-options; source chips use OrderSourceBadge; inquiry status uses WholesaleInquiryStatusBadge; reject uses components/admin/reject-reason-dialog. Do not add page-local copies of these.
