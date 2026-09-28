---
paths:
  - app/Http/Controllers/Branch/PacksSavesController.php
---

# Http Controllers Branch

## Packs & Saves is GET-only until persist
Branch Packs & Saves is UI-only: GET index/create only, no POST/store, no pack tables. Index dummy packs are placeholders, not catalog data. Create availableProducts are this branch's inventory SKUs with status true and listDisplayForProductContext prices. Do not persist packs yet.
