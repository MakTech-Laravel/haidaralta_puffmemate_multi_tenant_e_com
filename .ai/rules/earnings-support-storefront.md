---
paths:
  - 'app/Http/Controllers/Driver/EarningsController.php,resources/js/pages/driver/earnings/**,app/Support/Storefront/DemoCatalog.php'
---

# Earnings Support Storefront

## Driver earnings uses demo payout summary
Driver Earnings is GET /driver/earnings from DemoCatalog::driverEarnings() until real payouts exist. Keep it a compact pay summary with completed-drop rows, not an admin table.
