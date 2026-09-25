---
paths:
  - 'resources/js/components/storefront/cart/**'
---

# Cart

## Cart qty updates stay in place
Quantity and remove must use syncCartQuantity/removeCartItem (async Inertia patch/delete with preserveState, preserveScroll, preserveUrl, only cart props, and optimistic totals). Do not call router.patch/delete with only preserveScroll — that remounts the page like a navigation.

## Add to cart stays in place
List and PDP add-to-cart use addToCart() (async POST cart.store with preserveState/Url, only cart props). On success show a stacked Sonner toast ("Product added to cart" + View Cart). Do not open the header sheet automatically — only the cart icon or the toast View Cart action may dispatch storefront:cart-open. View cart / Checkout (and any other full page visit) must close the sheet; leave it open for async cart qty/remove. Do not router.post('/cart') as a full visit. The server must not flash a second toast on store.
