---
paths:
  - app/Support/Storefront/DemoCatalog.php
---

# Support Storefront

## Driver catalog assignment is capacity not suburb batches
Driver demo orders keep Australian areaId/areaLabel on each address for maps and search. Do not batch the catalog or UI by suburb, treat a suburb as one run, or refuse splitting the same streets across drivers. Place names stay Australian.
