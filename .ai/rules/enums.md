---
paths:
  - app/Enums/ShiftAudience.php
---

# Enums

## Shift audience all staff driver
shift_templates.audience is ShiftAudience all|staff|driver (default all). For whom? on create/edit. Pre-handover it belongs on create_shift_templates, not a Schema::table mod. Unique name stays per owner/branch, not per audience.
