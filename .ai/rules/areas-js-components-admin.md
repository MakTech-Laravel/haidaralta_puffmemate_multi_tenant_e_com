---
paths:
  - 'app/Models/BranchArea.php,app/Services/Branch/BranchAreaService.php,app/Http/Controllers/{Platform,Branch}/*Area*Controller.php,resources/js/pages/{platform,branch}/**/areas/**,resources/js/components/admin/branch-area-form.tsx'
---

# Areas Js Components Admin

## Branch areas are delivery zones CRUD only
branch_areas are named delivery/service zones (name, address+lat/lng, city, state; optional postal_code, notes, is_active) linked to many branches via `branch_area_branch`. Managed on Platform (view-branches / edit-branches under branches/{branch}/areas) and Branch portal (manage-branch-settings, Settings → Areas). Shared BranchAreaService + BranchAreaForm (AddressSuggest + CheckoutMapDialog). Assign = attach; do not steal membership. Do not wire checkout or BranchCandidateFinder to areas yet; delivery_radius_km on branches stays separate.
