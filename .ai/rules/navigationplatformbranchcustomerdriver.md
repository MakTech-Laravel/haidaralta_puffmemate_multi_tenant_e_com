---
paths:
  - 'resources/js/components/{navigation,platform,branch,customer,driver}/**'
---

# Navigationplatformbranchcustomerdriver

## Portal sidebar nav uses shared builders
Define portal sidebar trees with navLink / navGroup / navSection from resources/js/components/navigation. Filter with filterNavNodes + canAny (filterNavSections for labeled blocks). Do not add portal-specific sidebar item components or extend legacy AppSidebar/NavMain for platform/branch/customer/driver. UI stays on shadcn Sidebar primitives in SidebarNav.
