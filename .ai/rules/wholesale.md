---
paths:
  - 'app/Http/Controllers/Platform/WholesaleInquiryController.php,resources/js/pages/platform/wholesale-inquiries/**,resources/js/components/platform/wholesale/**'
---

# Wholesale

## Wholesale place-order page is UI shell only
GET platform.wholesale-inquiries.place-order renders place-order with inquiry contact defaults plus static demo branches/products. Client filters products by selected branch; Place order is a toast no-op. No order POST, stock APIs, or payment yet. Entry from show CTA and index row action.

## Wholesale place-order uses live branches
placeOrder loads all Branch id+name ordered by name for the Select. Products remain static demo catalog shown for any selected branch (not filtered by branch_id) until live stock is wired. No FALLBACK_IMAGE on product tiles; null image = empty muted box.

## Wholesale place-order uses InfiniteScroll branch stock
placeOrder uses StockPageContext::optionalBranch + ?branch= reload. Branch picker is InfiniteScrollCombobox against platform.wholesale-inquiries.branch-options (manage-users). Products are live branch inventory (available qty > 0) mapped to id/name/sku/price/stock/image. Demo catalog removed. Place order CTA remains toast-only.
