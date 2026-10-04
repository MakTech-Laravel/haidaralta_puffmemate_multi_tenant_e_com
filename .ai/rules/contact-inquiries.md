---
paths:
  - 'app/Http/Controllers/**/ContactInquiryController.php,app/Http/Requests/Storefront/StoreContactInquiryRequest.php,resources/js/components/storefront/contact/**,resources/js/pages/platform/contact-inquiries/**,routes/public.php,routes/platform.php'
---

# Contact Inquiries

## Contact form persists to platform inbox
Storefront POST /contact (throttle:10,1) creates ContactInquiry via StoreContactInquiryRequest: full_name/phone/type/message required; email nullable. type is string column cast to ContactInquiryType (includes others). Platform inbox /platform/contact-inquiries under manage-users: DataTable + show marks new→read. Form fields are Full name, Phone, Email, Type select, Message — no first/last name.
