---
paths:
    - 'app/Http/Controllers/{Platform,Branch}/Reports/**, resources/js/pages/{platform,branch}/reports/**'
---

# Platformbranch Reports

## Price history lives under Reports

Price history is a report, not an inline Manage stock card. Live URL is reports/price-history (controllers App\Http\Controllers\{Platform,Branch}\Reports\PriceHistoryController, pages */reports/price-history). Sidebar: Reports → Price history immediately after Stock transactions. Permission stays manage-branch-inventories / manage-branch-products. Product then Branch comboboxes first, plus source/from/to and Spatie pagination. Do not restore an inline last-N history list on Manage.
