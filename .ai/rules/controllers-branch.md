---
paths:
  - 'app/Models/Branch.php,app/Http/Controllers/Branch/**'
---

# Controllers Branch

## Branch pages use toPortalContext
Branch portal Inertia pages must pass branch via Branch::toPortalContext() (id, name, slug, logo_url). Do not use only(['id','name','slug']) — page props overwrite shared branch and strip the sidebar logo.
