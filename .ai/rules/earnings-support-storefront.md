---
paths:
  - 'app/Http/Controllers/Driver/EarningsController.php,resources/js/pages/driver/earnings/**,app/Services/Driver/DriverEarningsService.php'
---

# Earnings Support Storefront

## Driver earnings is a period collections summary
Driver Earnings is GET /driver/earnings via DriverEarningsService. Filter with period=today|7d|30d (default today). Totals and day-grouped rows come from payments the driver collected (Collected/Verified on collected_at). Show previous-period performance on the same window. Keep it a compact pay summary with completed-drop rows, not an admin table. Do not use DemoCatalog for this page.
