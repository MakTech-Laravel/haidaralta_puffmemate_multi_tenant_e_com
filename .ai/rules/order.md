---
paths:
  - app/Services/Order/OrderAllocationEngine.php
  - app/Actions/Order/PlaceOrderAction.php
  - app/Actions/Order/PlaceDriverOrderAction.php
---

# Order

## Null delivery radius means the branch can fulfil any pin
delivery_radius_km null = deliver anywhere, so those branches stay in the allocation candidate set. Only a set radius excludes a pin outside it. Do not fall back to ranked->take(1) when unlimited-radius branches exist — that made a -32 pin use empty Newcastle and ignore Sydney stock.

## Local stock beats nearby transfer
OrderAllocationEngine ranking starts with needs_transfer. A branch that can cover the cart locally always beats a closer empty branch plus an inbound transfer, even when the empty branch has an unlimited radius. Do not restore BranchAllocationService or split one cart across fulfillment branches.

## Driver sales use central orders
Driver Place Order writes the central orders table with source=driver_sale and placed_by=the selling driver. order_fulfillments.driver_id stays null (delivery only). A sale is delivered immediately, stock comes only from the driver's single active branch assignment, COD stays pending_collection, and POS/payment link is collected by that driver. Cancelling a delivered driver sale is rejected; cancelling before a terminal status returns bag stock once.
