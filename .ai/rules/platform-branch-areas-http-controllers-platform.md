---
paths:
  - 'resources/js/pages/platform/branch-areas/**,app/Http/Controllers/Platform/BranchAreasController.php,routes/platform.php'
---

# Platform Branch Areas Http Controllers Platform

## Platform area show/edit live under branch-areas
Platform area show/edit/update/destroy URLs are /platform/branch-areas/{area} so sidebar Areas stays active (isPathActive prefix-match). Nested /platform/branches/{branch}/areas/{area} show|edit 302 to the global routes. Do not put area detail pages under /platform/branches/ — that lights up Branches.
