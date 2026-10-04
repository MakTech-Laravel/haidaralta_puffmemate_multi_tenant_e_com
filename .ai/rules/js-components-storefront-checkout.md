---
paths:
  - 'resources/js/pages/storefront/checkout*.tsx,resources/js/components/storefront/checkout/**'
---

# Js Components Storefront Checkout

## Sticky Your order panels must scroll inside the aside
Checkout and confirmation Your order asides use lg:sticky. Cap with lg:max-h-[calc(100vh-8rem)], flex column, overflow-hidden on the aside, and overflow-y-auto on the item list (min-h-0 flex-1). Never let a tall line list grow the sticky panel past the viewport — that empties the left column on scroll and spills items outside the accent background.

## Sticky Your order panels must scroll inside the aside
Checkout and confirmation Your order asides use h-fit + lg:sticky. Cap the item list with max-h-56 overflow-y-auto at every breakpoint. Do not stretch the aside to viewport height (no lg:max-h-[100vh] / flex-1 list) — that leaves a large empty gap beside the shorter left column.
