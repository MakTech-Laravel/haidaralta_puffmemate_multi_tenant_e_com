---
paths:
  - 'app/Http/Controllers/Storefront/WholesaleInquiryController.php,app/Http/Controllers/Platform/WholesaleInquiryController.php,resources/js/components/storefront/wholesale/**,resources/js/pages/platform/wholesale-inquiries/**,routes/public.php,routes/platform.php'
---

# Wholesale Inquiries

## Wholesale inquiries persist to platform inbox
Storefront POST /wholesale (throttle:10,1) creates WholesaleInquiry status=new via StoreWholesaleInquiryRequest (full_name/phone/address/message required; email/company/ask_question nullable). Contact form stays client-only. Platform inbox at /platform/wholesale-inquiries under manage-users: index DataTable (search name/email/phone, filter status, sort -created_at); show marks new→read + read_at. No email/notifications in this pass.
