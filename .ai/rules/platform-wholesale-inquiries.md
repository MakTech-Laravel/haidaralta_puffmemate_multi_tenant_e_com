---
paths:
  - 'app/Http/Controllers/**/WholesaleInquiryController.php,app/Http/Requests/Storefront/StoreWholesaleInquiryRequest.php,resources/js/components/storefront/wholesale/**,resources/js/pages/platform/wholesale-inquiries/**'
---

# Platform Wholesale Inquiries

## Wholesale inquiry uses map pin, no ask_question

Storefront wholesale form has no ask_question field/column. Address uses CheckoutMapDialog; latitude/longitude are required on store. Platform show displays map pin coords. CMS wholesale form keys omit askQuestion*.

## Show page review actions

Show renders requested products, review card (reviewer, reviewed_at, order link, rejection reason), customer account link and an internal admin_note (PATCH platform.wholesale-inquiries.note). Reject (POST .reject) and Place order are only enabled while is_open. Index rows expose items_count, order link (when canViewOrders) and hide Place order for closed inquiries.
