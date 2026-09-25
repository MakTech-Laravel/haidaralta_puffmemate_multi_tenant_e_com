---
paths:
  - 'resources/js/{pages,components}/storefront/**,app/Http/Controllers/Storefront/{Cart,Checkout}Controller.php'
---

# Storefront Http Controllers Storefront

## Cart and checkout are live models only
Storefront cart, checkout, header sheet, and place-order confirmation read Cart, Address, Coupon, DeliveryMethod, and PaymentGatewaySetting models. Do not import DemoCatalog, storefront-demo, storefront-cart, or storefront-checkout. Guest carts live on the session until login merge; lines stay until a successful place order.
