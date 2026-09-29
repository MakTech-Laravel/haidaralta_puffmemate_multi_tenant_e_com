---
paths:
  - app/Services/Order/OrderAllocationEngine.php
  - app/Actions/Order/PlaceOrderAction.php
---

# Order

## Null delivery radius means the branch can fulfil any pin
delivery_radius_km null = deliver anywhere, so those branches stay in the allocation candidate set. Only a set radius excludes a pin outside it. Do not fall back to ranked->take(1) when unlimited-radius branches exist — that made a -32 pin use empty Newcastle and ignore Sydney stock.

## Local stock beats nearby transfer
OrderAllocationEngine ranking starts with needs_transfer. A branch that can cover the cart locally always beats a closer empty branch plus an inbound transfer, even when the empty branch has an unlimited radius. Do not restore BranchAllocationService or split one cart across fulfillment branches.
