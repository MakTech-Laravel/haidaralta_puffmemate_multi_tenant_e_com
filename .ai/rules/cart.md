---
paths:
  - 'resources/js/components/storefront/cart/**'
---

# Cart

## Cart qty updates stay in place
Quantity and remove must use syncCartQuantity/removeCartItem (async Inertia patch/delete with preserveState, preserveScroll, preserveUrl, only cart props, and optimistic totals). Do not call router.patch/delete with only preserveScroll — that remounts the page like a navigation.

## Add to cart stays in place
List and PDP add-to-cart use addToCart() (async POST cart.store with preserveState/Url, only cart props, open the header sheet on success). Do not router.post('/cart') as a full visit.
