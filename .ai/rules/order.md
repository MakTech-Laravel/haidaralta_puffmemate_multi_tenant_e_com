---
paths:
  - app/Services/Order/BranchAllocationService.php
---

# Order

## Null delivery radius means the branch can fulfil any pin
delivery_radius_km null = deliver anywhere, so those branches stay in the allocation candidate set. Only a set radius excludes a pin outside it. Do not fall back to ranked->take(1) when unlimited-radius branches exist — that made a -32 pin use empty Newcastle and ignore Sydney stock.
