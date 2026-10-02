---
paths:
  - 'resources/js/components/{platform,branch}/nav.ts,resources/js/pages/platform/branch-areas/**,app/Http/Controllers/Platform/BranchAreasController.php'
---

# Branch Areas Http Controllers Platform

## Areas nav is top-level branch and Platform Branch management
Branch portal Areas is a top-level navLink (after Order management, before Conversations), not under Settings — permission manage-branch-settings, href /branch/{slug}/areas. Platform Branch management includes Areas → /platform/branch-areas (global list with filter[branch_id], Staff-style). Nested platform.branches.areas.index 302s to that filtered global URL. Create/edit/show stay nested under branches/{branch}/areas.
