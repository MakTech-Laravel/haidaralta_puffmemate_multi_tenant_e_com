---
paths:
  - 'resources/js/components/driver/nav.ts,app/Http/Controllers/Driver/OrderController.php,resources/js/pages/driver/history.tsx'
---

# Js Pages Driver

## Old orders is sidebar-only history
Old orders is a desktop sidebar item at GET /driver/history (OrderController::history), not a fifth-tab replacement. Do not put it in DriverMobileNav. Today’s Orders tab stays /driver/orders.
