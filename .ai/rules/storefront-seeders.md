---
paths:
  - 'app/Services/Catalog/PriceRangeService.php,app/Models/ShopPriceFilterSetting.php,resources/js/pages/platform/catalog/price-ranges/**,resources/js/components/storefront/shop-filters-sidebar.tsx,config/catalog.php,database/seeders/PriceRangeSeeder.php'
---

# Storefront Seeders

## Shop slider bounds are platform-managed
Shop dual-range slider floor/ceiling come from singleton shop_price_filter_settings (slider_min/slider_max), editable on Shop price ranges via PUT platform.price-ranges.bounds. Defaults from config catalog.shop_price_slider_min/max (0 / 50000). Storefront never shows “Any”—bubbles and inputs always show fixed numbers. At floor/ceiling the filter commits null (no bound) so open-ended presets like $10,000 & above still match products above the ceiling. PriceRangeSeeder seeds $10,000 & above (min 10000, max null) and creates settings if missing.
