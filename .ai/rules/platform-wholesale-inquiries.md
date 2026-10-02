---
paths:
  - 'app/Http/Controllers/**/WholesaleInquiryController.php,app/Http/Requests/Storefront/StoreWholesaleInquiryRequest.php,resources/js/components/storefront/wholesale/**,resources/js/pages/platform/wholesale-inquiries/**'
---

# Platform Wholesale Inquiries

## Wholesale inquiry uses map pin, no ask_question
Storefront wholesale form has no ask_question field/column. Address uses CheckoutMapDialog; latitude/longitude are required on store. Platform show displays map pin coords. CMS wholesale form keys omit askQuestion*.
