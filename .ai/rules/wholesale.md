---
paths:
  - 'app/Http/Controllers/Platform/WholesaleInquiryController.php,app/Http/Controllers/Platform/WholesaleOrderController.php,app/Actions/Order/PlaceWholesaleOrderAction.php,resources/js/pages/platform/wholesale-inquiries/**,resources/js/components/platform/wholesale/**'
---

# Wholesale

## End-to-end flow

Storefront inquiry (optional product lines) → platform review (read / reject / note) → admin places a Wholesale order for one branch → branch confirms/packs and assigns a driver → driver delivers with the placement OTP. After placement the order is a normal Order (source=wholesale) and reuses the existing fulfillment, driver and OTP pipeline; do not fork those flows for wholesale.

## Permission

All platform wholesale routes sit under `permission:manage-wholesale` (PermissionEnum::MANAGE_WHOLESALE, platform team). After adding it on deploy, reseed RolesAndPermissionsSeeder. Branch staff are notified through MANAGE_BRANCH_ORDERS in their branch team.

## Placing the order

POST platform.wholesale-orders.store (throttle:30,1) → StoreWholesaleOrderRequest → PlaceWholesaleOrderAction. One transaction: idempotency_key replay, lock the open inquiry, resolve customer (inquiry customer_id first, else CustomerResolutionService provisional by phone), merge duplicate lines, lockOrdered branch inventory + available check (errors on items.{i}.quantity), admin unit prices, discount clamped to subtotal, delivery_fee explicit or BranchDeliveryQuote. Creates Order(source=Wholesale, status=Placed, placed_by, delivery_method=wholesale, snapshot incl. company/email), one Placed fulfillment, items, reservations, history, Payment(PendingCollection). Inquiry → Converted with order_id. After commit: OrderPlacementNotifier::issue, customer notification, BranchOrderNotifier::newOrder.

## Order numbers

Use App\Support\Order\OrderNumber::next() for every order source (checkout, driver sale, wholesale). Never re-implement the PMM-YYYY-#### sequence.

## Place-order page

Branch picker reloads with `only: ['branch','products']` + preserveState. Lines prefill from inquiry items; price defaults to BranchProductPriceDisplay::wholesaleForProductContext and is editable. Delivery fee auto-quotes from platform.wholesale-orders.delivery-quote until the admin edits it. Short or unstocked lines block submit. Submit uses a per-mount crypto.randomUUID idempotency key.

## Variants

Product rows are keyed like DriverPlaceOrderCatalog: `{product}` or `{product}:{variant}`, one per active branch inventory row; a variant uses its own branch price row when one exists, else the product's. Lines submit `variant_id`; the action rejects variants of another product and snapshots `Product · Variant` + variant SKU (wholesale only — other order sources keep the product name). Storefront picker rows carry active `variants`; an inquiry line may stay on "Any option" (variant_id null), and product+variant must be unique per inquiry.
