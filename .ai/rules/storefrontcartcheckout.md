---
paths:
  - 'resources/js/components/storefront/{cart,checkout}/**'
---

# Storefrontcartcheckout

## Cart line quantity is capped by CartService::MAX_LINE_QUANTITY
CartService::MAX_LINE_QUANTITY (99) is the only source for the per-line cap: both cart FormRequests validate against it, add/merge clamp to it, and the cart payload exposes it as item.maxQuantity. The UI must disable + at maxQuantity and syncCartQuantity refuses values above it; optimistic updates past the server cap roll back silently, which caused the old 'jumps back to 99' bug.
