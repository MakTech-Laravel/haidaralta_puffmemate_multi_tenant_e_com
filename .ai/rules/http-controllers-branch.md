---
paths:
  - app/Http/Controllers/Branch/PacksSavesController.php
---

# Http Controllers Branch

## Packs persist per branch with reserved stock
Branch Packs & Saves is persisted CRUD (index/create/store/show/edit/update/destroy) behind manage-branch-products. Packs are scoped by branch_id; {pack} binds by slug for the current branch URL. Available products are this branch's catalog-active inventory SKUs only. Store/update go through PackService (Reserve/Release). Do not use dummy index rows.
