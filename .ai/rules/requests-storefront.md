---
paths:
  - 'resources/js/components/storefront/wholesale/**,resources/js/lib/nominatim.ts,app/Http/Requests/Storefront/StoreWholesaleInquiryRequest.php'
---

# Requests Storefront

## Wholesale address is suggest- or map-selected only
Wholesale address field uses AddressSuggestInput (Nominatim search). Free-typed text is query-only and never submitted; committed address + lat/lng come only from a suggestion pick or CheckoutMapDialog reverse-geocode. Retyping after a selection clears address/lat/lng until the user picks again.
