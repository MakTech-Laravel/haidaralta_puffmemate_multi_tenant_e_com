---
paths:
  - 'app/Http/Requests/Storefront/PlaceOrderRequest.php,app/Services/Storefront/CheckoutService.php,resources/js/{pages,components}/storefront/checkout/**'
---

# Pagescomponents Storefront Checkout

## Checkout uses one full name
users.name, addresses.receiver_name, and customer_addresses.name are a single full-name string. There are no first_name/last_name columns. Checkout validates and posts `name`, stores it on receiver_name and shipping_address_snapshot.name, and shows one Full name field. Do not split receiver_name.
