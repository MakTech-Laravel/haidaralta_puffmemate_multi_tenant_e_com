---
paths:
  - 'app/Http/Controllers/Platform/WholesaleInquiryController.php,resources/js/pages/platform/wholesale-inquiries/**,routes/platform.php'
---

# Pages Platform Wholesale Inquiries

## Index Place Order opens blank create route
GET platform.wholesale-inquiries.place-order.create renders place-order with inquiry=null and blank customer fields. Index header Place Order CTA uses createPlaceOrder. Inquiry-bound place-order ({id}/place-order) still prefills customer from the inquiry.
