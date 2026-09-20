---
paths:
  - 'app/Http/Controllers/Driver/**'
---

# Controllers Driver

## Driver controllers pass remaining and completed stops
Driver dashboard and route pass remaining/completed assigned stops from DemoCatalog::driverRemainingOrders()/driverCompletedOrders(). Do not pass currentArea, laterAreas, areaCount, or suburb batches. Admins assign by load: mix places on one driver, or split a busy street across drivers.
