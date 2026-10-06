---
paths:
  - 'app/Http/Controllers/Driver/PlaceOrderController.php,app/Support/Driver/DriverPlaceOrderCatalog.php,resources/js/components/driver/place-order/**'
---

# Driver Place Order

## Place Order products come from driver_inventories
GET /driver/place-order products are built from the signed-in driver’s driver_inventories (quantity > 0, active products), aggregated by product_id+variant_id. Customers and paymentMethods stay DemoCatalog until live Place Order create exists. Do not pass DemoCatalog product stock for this screen.
