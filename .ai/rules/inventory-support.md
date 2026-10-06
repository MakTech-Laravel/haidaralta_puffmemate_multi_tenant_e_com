---
paths:
  - 'app/Services/Catalog/**,app/Services/Inventory/**,app/Support/PackAvailability.php'
---

# Inventory Support

## Pack sell qty reserves and auto-off never auto-on
Creating or increasing pack.quantity Reserves line_qty × sell qty from each product's base (null variant) on-hand via InventoryService::adjust. Decreasing or deleting Releases. After every base-stock adjust, PackAvailability::syncForProduct turns a pack off when quantity is 0 or remaining on-hand is below that pack's line qty. Never auto-set status true on restock; staff must turn Active on in edit (re-checks remaining ≥ line qty). A pack that reserved the last units stays active; other packs still needing that SKU turn off.
