---
paths:
  - 'app/Http/Controllers/Storefront/WholesaleInquiryController.php,app/Http/Controllers/Platform/WholesaleInquiryController.php,app/Http/Controllers/Customer/WholesaleRequestController.php,app/Services/Storefront/WholesaleInquiryService.php,resources/js/components/storefront/wholesale/**,resources/js/pages/platform/wholesale-inquiries/**,resources/js/pages/customer/wholesale-requests/**,routes/public.php,routes/platform.php,routes/customer.php'
---

# Wholesale Inquiries

## Storefront submission

POST /wholesale (throttle:10,1) → StoreWholesaleInquiryRequest → WholesaleInquiryService::store. full_name/phone/address/lat/lng required; message is required only when no items. items[] (max 50, active product_id, optional active variant_id, quantity 1–100000) are snapshotted into wholesale_inquiry_items; the after() hook rejects variants of another product and duplicate product+variant lines, then enforces Product.min_wholesale_qty. A signed-in customer is linked via customer_id. Product picker loads GET /wholesale/products (throttle:60,1).

## Statuses

WholesaleInquiryStatus: new → read (platform show) → converted (order placed) | rejected (reason required). isOpen() = new|read; closed inquiries cannot be rejected again or converted. Use label() for staff and customerLabel() for the customer portal.

## Notifications

New inquiry → notify_users to PermissionRecipientResolver::platform(MANAGE_WHOLESALE) with link_url to the platform show. Reject → notifies the linked customer with link_url to customer.wholesale-requests.show. Never hard-code recipient ids.

## Customer portal

customer.wholesale-requests.index/show list only the signed-in customer's inquiries (404 otherwise). Customer order show exposes wholesaleRequestId for wholesale orders.
