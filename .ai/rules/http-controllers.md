---
paths:
  - 'app/Services/Storefront/ContactInquiryService.php,app/Http/Controllers/**/ContactInquiryController.php'
  - 'app/Services/Storefront/WholesaleInquiryService.php,app/Http/Controllers/**/WholesaleInquiryController.php'
---

# Http Controllers

## Contact inquiry create and mark-read use ContactInquiryService
Storefront ContactInquiryController@store and Platform ContactInquiryController@show go through App\Services\Storefront\ContactInquiryService (store + markRead). Do not create ContactInquiry rows or flip status inline in controllers.

## Wholesale inquiry create and mark-read use WholesaleInquiryService
Storefront WholesaleInquiryController@store and Platform WholesaleInquiryController@show go through App\Services\Storefront\WholesaleInquiryService (store + markRead). Do not create WholesaleInquiry rows or flip status inline in controllers. Model markRead() delegates to the service.
