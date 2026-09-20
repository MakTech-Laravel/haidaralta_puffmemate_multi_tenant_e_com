---
paths:
  - 'app/Http/Controllers/Driver/**'
---

# Controllers Driver

## Driver controllers pass neighbourhood batches
Driver dashboard and route treat assigned orders as neighbourhood batches. Pass currentArea, laterAreas, and areaCount from DemoCatalog::driverAssignedAreas()/driverRunAreas(). Do not present the run as one trip per customer.
