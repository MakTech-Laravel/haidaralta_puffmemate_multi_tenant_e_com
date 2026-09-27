---
paths:
  - 'app/Services/Storefront/CheckoutService.php,resources/js/components/storefront/checkout/**'
---

# Storefront Checkout

## Checkout delivery methods are not free
DeliveryMethod.fee is a leftover flat amount (seeded 0). Real shipping is ShippingCalculator distance cost at place(). Never label method.formattedFee as FREE. Checkout cards and the order summary say Calculated at checkout.
