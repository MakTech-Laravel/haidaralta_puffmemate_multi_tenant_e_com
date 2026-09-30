---
paths:
  - 'resources/js/components/driver/nav.ts,app/Http/Controllers/Driver/OrderController.php,resources/js/pages/driver/history.tsx'
---

# Js Pages Driver

## Old orders is not in driver nav
GET /driver/history (OrderController::history) may remain as a route for deep links and tests, but must not appear in driverNavItems (sidebar or tabs). Today’s Orders tab stays /driver/orders. The fifth field tab is Place order.
