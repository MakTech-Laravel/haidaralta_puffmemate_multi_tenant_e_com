---
paths:
  - 'resources/js/components/admin/branch-area-detail.tsx,app/Services/Branch/BranchAreaService.php,resources/js/pages/{platform,branch}/**/areas/**'
---

# Areas

## Areas show uses BranchAreaDetail + map links
Platform and Branch area show pages render shared BranchAreaDetail (header, Details/Location cards, View on map via BranchLocationMapDialog). BranchAreaService::detail includes linked branches plus map via BranchMap::links when coords exist. Platform “Add to branch” attaches without removing other memberships. Create/edit use sectioned BranchAreaForm (Identity / Location / Notes).
