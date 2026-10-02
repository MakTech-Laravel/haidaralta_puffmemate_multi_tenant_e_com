---
paths:
  - 'app/Services/Storefront/ContactInquiryService.php,app/Http/Controllers/**/ContactInquiryController.php'
---

# Http Controllers

## Contact inquiry create and mark-read use ContactInquiryService
Storefront ContactInquiryController@store and Platform ContactInquiryController@show go through App\Services\Storefront\ContactInquiryService (store + markRead). Do not create ContactInquiry rows or flip status inline in controllers.
