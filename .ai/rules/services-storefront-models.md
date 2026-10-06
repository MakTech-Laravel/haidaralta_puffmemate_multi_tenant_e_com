---
paths:
  - 'app/Http/Controllers/Storefront/ProductController.php,app/Http/Controllers/Storefront/CartController.php,app/Services/Storefront/ProductViewService.php,app/Models/ProductViewLog.php'
---

# Services Storefront Models

## Product views are unique per IP for 24 hours
products.views increments via ProductViewService on PDP show (not prefetch/partial) and cart.store when count_view is true. One count per product_id+IP per sliding 24h; product_view_logs store ip/user_agent/viewed_at. Daily product-views:prune deletes logs older than 7 days without decreasing products.views. Cart flashes view_counted for the ProductCard local bump.
