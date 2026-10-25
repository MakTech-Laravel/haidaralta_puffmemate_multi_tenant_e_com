---
paths:
  - app/Services/Order/OrderAllocationEngine.php
  - app/Services/Order/Allocation/AreaBranchResolver.php
  - app/Services/Order/Allocation/AreaCheckoutAllocator.php
  - app/Actions/Order/PlaceOrderAction.php
  - app/Actions/Order/PlaceDriverOrderAction.php
---

# Order

## Cart checkout uses area nearest branch, not radius allocation
`PlaceOrderAction` / storefront cart quote use `AreaCheckoutAllocator` + `AreaBranchResolver` + `Australia::contains`. Inside area polygon → nearest linked branch (`area_nearest`); outside areas but in AU → nearest active branch (`nearest_active`); outside AU → address error. Do not route cart checkout through `OrderAllocationEngine` or `delivery_radius_km`. Shortfalls require `accept_late_delivery`; reserve only `local_quantity`; set `order_items.is_late_delivery` / `orders.accepts_late_delivery`; never run `StockTransferPlanner` for late shortfalls. Pack and driver placement stay on their existing branch-scoped paths.

## Null delivery radius means the branch can fulfil any pin
delivery_radius_km null = deliver anywhere, so those branches stay in the allocation candidate set for OrderAllocationEngine (non-cart paths that still use it). Only a set radius excludes a pin outside it. Do not fall back to ranked->take(1) when unlimited-radius branches exist — that made a -32 pin use empty Newcastle and ignore Sydney stock.

## Local stock beats nearby transfer
OrderAllocationEngine ranking starts with needs_transfer. A branch that can cover the cart locally always beats a closer empty branch plus an inbound transfer, even when the empty branch has an unlimited radius. Do not restore BranchAllocationService or split one cart across fulfillment branches.

## Driver sales use central orders
Driver Place Order writes the central orders table with source=driver_sale and placed_by=the selling driver. order_fulfillments.driver_id stays null (delivery only). A sale is delivered immediately, stock comes only from the driver's single active branch assignment, COD stays pending_collection, and POS/payment link is collected by that driver. Cancelling a delivered driver sale is rejected; cancelling before a terminal status returns bag stock once.
