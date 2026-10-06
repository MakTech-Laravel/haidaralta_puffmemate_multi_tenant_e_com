# Order Management Current State Audit

Audit date: 28 September 2026.

This document describes the implemented Order Management Phase 1 system. It is a read-only audit of the current Laravel 13 + Inertia React application. No code, migrations, or schema were changed to produce it.

Business model in force: one company owns every branch. There are no third-party sellers. The platform owns the catalog. Branches hold inventory and fulfill deliveries. Customers see a single master order and never see which branch fulfilled it.

User types (`App\Enums\UserType`):

| Type | Guard | Authorization model |
| --- | --- | --- |
| Customer | `customer` | Guard + ownership (`orders.customer_id`). No Spatie roles. |
| Branch staff | `branch` | Guard + Spatie permissions scoped to the current branch. |
| Platform admin | `platform` | Guard + Spatie permissions. Sees every branch. |
| Driver | `driver` | Guard only. No Spatie roles. Order screens are still demo data. |

There is no `OrderPolicy`. Order access is enforced by route middleware, customer ownership checks, and branch scoping in the branch controller. `ConversationPolicy` exists and is separate.

There are no order jobs, no order notifications, and no order domain events. The only events tied to this area are conversation broadcasts (`MessageCreated`, `ConversationClosed`, `ConversationInboxUpdated`, `ConversationReceiptsUpdated`).

---

## 1. System Overview

A customer order is a **master order** (`orders`) plus one or more **fulfillments** (`order_fulfillments`). Each fulfillment belongs to one branch and holds the line items that branch will deliver. Shipping is calculated per fulfillment from distance and the branch shipping rule, then summed onto the master order.

Placement is synchronous inside `CheckoutService::place()`. Allocation reads live inventory but does **not** reserve or deduct stock. `order_fulfillments.driver_id` exists and is nullable. Placement never sets it. No UI assigns a driver to a fulfillment.

Customer, branch, and platform order screens read real tables. Driver dashboard, route, orders, history, earnings, order detail, chat, and “mark delivered” still read `DemoCatalog` and do not persist anything.

```text
Customer cart
    → Checkout (pin, quote, place)
    → BranchAllocationService (nearest eligible branch + stock check)
    → orders + order_fulfillments + order_items + payments
    → Branch staff update fulfillment status / mark fulfillment paid
    → OrderStatusAggregator rolls fulfillment status into the master order
    → Platform can override any fulfillment status and the master payment status
```

Driver assignment, driver delivery workflow, stock deduction, and customer/driver conversation screens are not implemented.

---

## 2. Database Schema

Engine observed: PostgreSQL. Types below match the live schema. Laravel migrations use the equivalent column builders (`id()`, `foreignId()`, `string`, `decimal`, `json`, `boolean`, `timestamps`).

Related tables that are not “order rows” but participate in placement: `addresses`, `coupons`, `coupon_redemptions`, `delivery_methods`, `delivery_time_slots`, `branch_shipping_rules`, `inventories`, `inventory_transactions`, `payment_gateway_settings`, `conversations`, `conversation_participants`, `messages`.

### orders

Purpose: customer-facing master order. Does not store a branch or driver.

| Column | Type | Purpose |
| --- | --- | --- |
| id | bigint PK | Internal id. Customer URLs do not use this. |
| order_number | varchar(32) unique | Public number, format `PMM-{year}-{sequence}` (example `PMM-2026-2451`). |
| customer_id | bigint FK → users, cascade delete | Order owner. |
| address_id | bigint FK → addresses, null on delete, nullable | Saved address row written at checkout. |
| coupon_id | bigint FK → coupons, null on delete, nullable | Applied coupon. |
| coupon_code | varchar, nullable | Snapshot of the code. |
| status | varchar(32), default `pending` | Master `OrderStatus`. |
| shipping_address_snapshot | json, nullable | `{name, phone, address, city, postal_code}` at placement. |
| latitude | numeric(10,7), nullable | Delivery pin. |
| longitude | numeric(10,7), nullable | Delivery pin. |
| subtotal | numeric(12,2), default 0 | Cart subtotal. |
| discount | numeric(12,2), default 0 | Coupon discount. |
| delivery_fee | numeric(12,2), default 0 | Sum of fulfillment shipping costs. |
| tax | numeric(12,2), default 0 | Always written as 0. |
| total_amount | numeric(12,2), default 0 | `subtotal - discount + delivery_fee`, floored at 0. |
| payment_status | varchar(32), default `unpaid` | Master `PaymentStatus`. |
| payment_method | varchar, nullable | Gateway key (`cod`, `pos`, `payment_link`). |
| delivery_method | varchar, nullable | `delivery_methods.key` (`same-day` or `australia-wide`). |
| delivery_date | date, nullable | Required only when the method requires a slot. |
| time_slot | varchar, nullable | Slot label snapshot, not the slot id. |
| notes | text, nullable | Optional checkout notes. Not collected by the current checkout form. |
| created_at / updated_at | timestamps | |

Indexes: primary `id`; unique `order_number`; `customer_id`; composite `(status, created_at)`.

### order_fulfillments

Purpose: internal per-branch delivery unit. Hidden from the customer payload.

| Column | Type | Purpose |
| --- | --- | --- |
| id | bigint PK | Branch order URLs bind this id. |
| order_id | bigint FK → orders, cascade | Parent master order. |
| branch_id | bigint FK → branches, restrict | Fulfilling branch. |
| driver_id | bigint FK → users, null on delete, nullable | Reserved. Placement does not set it. No assignment UI. |
| fulfillment_number | varchar(40) unique | `{order_number}-A`, `-B`, … in allocation order. |
| status | varchar(32), default `pending` | `FulfillmentStatus`. |
| distance_km | numeric(8,2), nullable | Haversine distance from pin to branch. Null if the branch has no coordinates. |
| shipping_cost | numeric(12,2), default 0 | This branch’s share of delivery. |
| paid_at | timestamp, nullable | Set when branch staff mark this fulfillment collected. Null means unpaid. |
| notes | text, nullable | Unused by placement. |
| created_at / updated_at | timestamps | |

Indexes: primary `id`; unique `fulfillment_number`; `order_id`; composite `(branch_id, status)`.

### order_items

Purpose: priced line snapshot. A split cart line can become more than one item row (one per fulfillment).

| Column | Type | Purpose |
| --- | --- | --- |
| id | bigint PK | |
| order_id | bigint FK → orders, cascade | Master order. |
| fulfillment_id | bigint FK → order_fulfillments, null on delete, nullable | Owning fulfillment. |
| product_id | bigint FK → products, restrict | Catalog product at placement. |
| variant_id | bigint FK → product_variants, null on delete, nullable | Optional variant. |
| product_name_snapshot | varchar | Name at placement. |
| sku_snapshot | varchar, nullable | SKU at placement. |
| product_snapshot | json, nullable | `name`, `slug`, `sku`, `image`, `description`, `brand`, `pack_label`. |
| quantity | integer | Quantity allocated to this fulfillment. |
| unit_price | numeric(12,2) | Catalog effective price at placement. |
| subtotal | numeric(12,2) | `unit_price * quantity`. |
| created_at / updated_at | timestamps | |

Indexes: primary `id`; `order_id`; `fulfillment_id`; `product_id`.

### order_status_histories

Purpose: append-only trail. Master rows have `fulfillment_id` null. Fulfillment rows set both ids.

| Column | Type | Purpose |
| --- | --- | --- |
| id | bigint PK | |
| order_id | bigint FK → orders, cascade | |
| fulfillment_id | bigint FK → order_fulfillments, cascade, nullable | Null = master order history. |
| old_status | varchar(32), nullable | Previous status value. Null on create. |
| new_status | varchar(32) | New status value (order or fulfillment enum, stored as a string). |
| comment | text, nullable | Human note (`Order placed`, `Fulfillment created`, `Status updated`, aggregator comment). |
| changed_by | bigint FK → users, null on delete, nullable | Actor. Aggregator recompute does not set this. |
| created_at / updated_at | timestamps | |

Indexes: primary `id`; `order_id`; `fulfillment_id`.

### payments

Purpose: one payment row created at placement. Offline gateways only in Phase 1.

| Column | Type | Purpose |
| --- | --- | --- |
| id | bigint PK | |
| order_id | bigint FK → orders, cascade | |
| gateway | varchar | Same key as `orders.payment_method`. |
| transaction_id | varchar, nullable | Unused at placement. |
| amount | numeric(12,2) | Order total. |
| currency | varchar(8), default `AUD` | |
| status | varchar(32), default `unpaid` | `PaymentStatus` on the payment row. |
| paid_at | timestamp, nullable | Not updated by the current mark-paid flow. |
| created_at / updated_at | timestamps | |

Indexes: primary `id`; `order_id`; `status`.

Important: marking a fulfillment or order paid updates `order_fulfillments.paid_at` and/or `orders.payment_status`. It does **not** update `payments.status` or `payments.paid_at`.

### payment_transactions

Purpose: event log for a payment.

| Column | Type | Purpose |
| --- | --- | --- |
| id | bigint PK | |
| payment_id | bigint FK → payments, cascade | |
| event_type | varchar | Placement writes `initiated`. |
| response | json, nullable | Placement writes `{"offline": true}`. |
| created_at / updated_at | timestamps | |

Index: `payment_id`.

### payment_gateway_settings

Purpose: which checkout methods are enabled. Not an order row.

| Column | Type | Purpose |
| --- | --- | --- |
| id | bigint PK | |
| key | varchar unique | `cod`, `pos`, `payment_link`. |
| label | varchar | Display label. |
| description | varchar, nullable | |
| enabled | boolean, default true | Checkout only offers enabled keys. |
| sort | smallint | |
| created_at / updated_at | timestamps | |

Seeded labels: Cash on delivery (COD), POS / Card on delivery, Payment link. Checkout copy tells the customer there is no online card entry. Placement always records the payment as unpaid and offline.

### coupon_redemptions

| Column | Type | Purpose |
| --- | --- | --- |
| id | bigint PK | |
| coupon_id | bigint FK → coupons, cascade | |
| order_id | bigint FK → orders, cascade | |
| user_id | bigint FK → users, cascade | |
| discount_amount | numeric(12,2) | Amount taken off this order. |
| created_at / updated_at | timestamps | |

Indexes: `coupon_id`, `order_id`, `user_id`. Written only when a coupon is applied at place.

### delivery_methods / delivery_time_slots

`delivery_methods`: `key` (unique), `label`, `description`, `fee` (leftover flat amount, seeded 0, **not** the checkout fee), `requires_slot`, `status`, `sort`.

`delivery_time_slots`: `delivery_method_id` FK cascade, `label`, `starts_at`, `ends_at`, `status`, `sort`. Index `(delivery_method_id, status, sort)`.

Real delivery money is `ShippingCalculator`, not `delivery_methods.fee`.

### branch_shipping_rules

One row per branch (`branch_id` unique, cascade). Columns: `base_fee`, `free_delivery_distance_km` (nullable), `cost_per_km`, `minimum_delivery_charge`, `status` (boolean). Missing rule falls back to `config/catalog.php` defaults.

### inventories (read at allocation, not written)

Unique `(branch_id, product_id, variant_id)`. `quantity` integer, `status` boolean. Allocation counts a row only when `status` is true. Placement does not decrement `quantity` and does not insert `inventory_transactions`.

### conversations (order-linked)

| Column | Type | Purpose |
| --- | --- | --- |
| id | bigint PK | |
| order_id | bigint FK → orders, cascade, **unique** | One conversation per order. |
| branch_id | bigint FK → branches, cascade | Branch of the **primary** (oldest) fulfillment, not every fulfilling branch. |
| status | varchar(16), default `open` | `open` / closed. |
| last_message_preview | varchar(160), nullable | |
| last_message_sender_id | bigint FK → users, null on delete, nullable | |
| last_message_at | timestamp, nullable | |
| closed_at | timestamp, nullable | |
| created_at / updated_at | timestamps | |

Indexes: unique `order_id`; `(branch_id, status)`; `last_message_at`.

`conversation_participants`: unique `(conversation_id, user_id)`; `role` (`customer`, `driver`, `branch_staff`, `platform_staff`); `last_delivered_message_id`; `last_read_message_id`; `joined_at`.

`messages`: `conversation_id`, `sender_id` nullable, `type` default `text`, `body`. Index `(conversation_id, id)`.

`message_attachments` and `message_reads` hang off messages. Attachments use the private `local` disk.

Checkout does **not** open a conversation. An operator opens one later from the branch or platform inbox.

---

## 3. Model Relationships

```text
User (customer)
  └── has many Order (customer_id)

Order
  ├── belongsTo User customer
  ├── belongsTo Address
  ├── belongsTo Coupon
  ├── hasMany OrderItem
  ├── hasMany OrderFulfillment
  ├── hasOne OrderFulfillment primaryFulfillment (oldest)
  ├── hasMany OrderStatusHistory (fulfillment_id is null)
  ├── hasMany Payment
  └── hasOne Conversation

OrderFulfillment
  ├── belongsTo Order
  ├── belongsTo Branch
  ├── belongsTo User driver (driver_id, unused)
  ├── hasMany OrderItem
  └── hasMany OrderStatusHistory

OrderItem
  ├── belongsTo Order
  ├── belongsTo OrderFulfillment
  ├── belongsTo Product
  └── belongsTo ProductVariant

Payment
  └── hasMany PaymentTransaction

Conversation
  ├── belongsTo Order
  ├── belongsTo Branch
  ├── hasMany ConversationParticipant
  └── hasMany Message
```

Models: `app/Models/Order.php`, `OrderItem.php`, `OrderFulfillment.php`, `OrderStatusHistory.php`, `Payment.php`, `PaymentTransaction.php`, `PaymentGatewaySetting.php`, `BranchShippingRule.php`, `DeliveryMethod.php`, `DeliveryTimeSlot.php`, `Conversation.php`, `DriverProfile.php`, `DriverBranchAssignment.php`, `DriverRoster.php`.

`Order::$dataTableSearchable` is `order_number`. Sortable: `order_number`, `status`, `total_amount`, `created_at`. Filterable: `status`, `payment_status`.

`OrderFulfillment` searchable: `fulfillment_number`. Sortable: `fulfillment_number`, `status`, `shipping_cost`, `created_at`. Filterable: `status`, `branch_id`.

Both order models use `AuditsChanges` (Spatie activity log) in addition to `order_status_histories`.

---

## 4. Order Creation Flow

Public checkout routes (`routes/public.php`, middleware `auth:customer`):

| Method | Path | Name | Handler |
| --- | --- | --- | --- |
| GET | `/checkout` | `checkout` | `Storefront\CheckoutController@index` |
| POST | `/checkout/coupon` | `checkout.coupon` | `applyCoupon` |
| DELETE | `/checkout/coupon` | `checkout.coupon.destroy` | `removeCoupon` |
| POST | `/checkout/shipping` | `checkout.shipping` | `quoteShipping` (JSON) |
| POST | `/checkout` | `checkout.store` | `store` |
| GET | `/checkout/confirmation/{order:order_number}` | `checkout.confirmation` | `confirmation` |

Flow:

1. **Cart.** `CartService` holds session or user cart lines (`Cart`, `CartItem`). Storefront cart pages are live models.
2. **Checkout page.** `CheckoutController@index` shares cart payload, saved addresses, delivery methods (no fee on the card), time slots, enabled payment methods, applied coupon, and any session shipping quote.
3. **Pin / quote.** `POST /checkout/shipping` validates latitude/longitude (`QuoteShippingRequest`) and calls `CheckoutService::quoteShipping()`, which runs the same allocator and sums `ShippingCalculator` costs. Result is JSON plus session key `storefront.checkout_shipping`. The React page uses `useHttp`, so the page does not remount.
4. **Place.** `PlaceOrderRequest` requires `name` (max 255, single full name — there is no `first_name` / `last_name`), mobile, address, latitude, longitude, delivery method, payment method, and accepted terms. Same-day also requires `delivery_date` and `time_slot_id`. City is nullable in validation; the Place order button still requires it in the UI.
5. **`CheckoutService::place()`** (one database transaction):
   - Rejects an empty cart, inactive products, non-positive catalog price, unknown delivery method, missing same-day slot, or a disabled payment method.
   - Calls `BranchAllocationService::allocate()`.
   - Quotes the coupon through `CouponService` when a code is present.
   - Sums per-branch shipping into `delivery_fee`.
   - Writes or updates `addresses` (`receiver_name` = the single `name`).
   - Creates the `orders` row, status `pending`, payment status `unpaid`.
   - Creates one `order_fulfillments` row per allocated branch. `driver_id` is omitted.
   - Creates `order_items` from catalog effective price and product snapshot.
   - Writes a fulfillment history row and a master “Order placed” history row (`changed_by` = customer).
   - Creates `payments` (`status` unpaid) and `payment_transactions` (`event_type` initiated, `response.offline` true).
   - Creates `coupon_redemptions` when a coupon applied.
   - Deletes cart lines and sets the cart status to `converted`.
6. **Redirect** to `/checkout/confirmation/{order_number}` with toast “Order placed.” Coupon and shipping session keys are cleared.

No event is dispatched. No job is queued. No notification is sent. No conversation is opened. No inventory row is changed.

Confirmation payload (`CheckoutService::confirmation()`) includes order number, line snapshots, totals, delivery label, address snapshot, and `viewOrderUrl` (`customer.orders.show`). It does not include `order.id`, branch names, or fulfillments.

---

## 5. Order Status System

### Master order status (`App\Enums\OrderStatus`)

| Value | Label | Terminal |
| --- | --- | --- |
| `pending` | Pending | no |
| `confirmed` | Confirmed | no |
| `processing` | Processing | no |
| `partially_delivered` | Partially delivered | no |
| `delivered` | Delivered | yes |
| `cancelled` | Cancelled | yes |

Placement always starts at `pending`. `confirmed` exists on the enum and in the customer UI map, but **nothing in the aggregator or forms sets `confirmed`**. It can only appear if written directly.

`OrderStatusAggregator::recompute()`:

- If the master order is already `cancelled`, it does not change.
- Every fulfillment `cancelled` → master `cancelled`.
- Every fulfillment is `delivered` or `cancelled`, and at least one is `delivered` → master `delivered`.
- At least one `delivered` and some still open → master `partially_delivered`. `partially_delivered` is only derived here, never posted by a form as a master status.
- Any fulfillment `processing` or `ready`, while the master is `pending`, `confirmed`, or `processing` → master `processing`.
- Otherwise the master stays unchanged (a mix of only `pending` and `cancelled` does not move the master).

When the target differs, the aggregator inserts a master history row (`fulfillment_id` null, comment “Recalculated from fulfillment statuses.”, no `changed_by`) and calls `OrderConversationCloser::transition()`, which saves `orders.status`. Terminal statuses close an existing conversation if one is open.

### Fulfillment status (`App\Enums\FulfillmentStatus`)

| Value | Label | Terminal |
| --- | --- | --- |
| `pending` | Pending | no |
| `processing` | Processing | no |
| `ready` | Ready | no |
| `delivered` | Delivered | yes |
| `cancelled` | Cancelled | no on the fulfillment enum’s `isTerminal()` only for `delivered` and `cancelled` — both are terminal |

There is no enforced state machine. `UpdateFulfillmentStatusRequest` accepts any `FulfillmentStatus` value. Branch and platform UIs expose every option in a select.

Who can change it:

| Actor | Route | Permission | Service |
| --- | --- | --- | --- |
| Branch staff | `PUT /branch/{branch}/orders/{fulfillment}/status` | `manage-branch-orders` | `FulfillmentStatusService::transition()` after `resolveForBranch()` (404 if the fulfillment is another branch). |
| Platform admin | `PUT /platform/orders/{order}/fulfillments/{fulfillment}/status` | `manage-orders` | Same service. 404 if the fulfillment does not belong to that order. |
| Customer | none | — | Cannot change status. |
| Driver | `POST /driver/orders/{order}/deliver` | guard only | Validates a demo OTP and flashes success. Does **not** call `FulfillmentStatusService` and does not load `Order`. |

`FulfillmentStatusService::transition()` no-ops when the status is unchanged. Otherwise it saves the fulfillment, writes `order_status_histories` with `changed_by` = the actor, then recomputes the master order.

Customer timeline is built only from master history (`fulfillment_id` null) plus a synthetic “current” step. Branch detail shows that fulfillment’s history. Platform detail shows master history labels as raw status strings.

---

## 6. Customer Module

Routes (`routes/customer.php`, middleware `ensure.guard:customer` + `phone.verified`):

| Method | Path | Name |
| --- | --- | --- |
| GET | `/orders` | `customer.orders.index` |
| GET | `/orders/{order:order_number}` | `customer.orders.show` |

Controller: `App\Http\Controllers\Customer\OrderController`.

`show` aborts 404 unless `orders.customer_id` is the authenticated customer. Binding is `order_number`, so `/orders/461` (database id) 404s. `/orders/PMM-2026-2451` resolves.

Pages:

- `resources/js/pages/customer/orders/index.tsx` — renders `OrderList`.
- `resources/js/pages/customer/orders/show.tsx` — order heading, status badge, products, address, timeline.

Components:

- `resources/js/components/customer/order-list.tsx` — cards with order number, date, status, up to two product chips, total. **No product image.** Links use `orderNumber`, not `id`.
- `order-track-products.tsx`, `order-track-address.tsx`, `order-delivery-timeline.tsx`
- `order-track-driver.tsx` — rendered only if `order.driver` is present. The controller does not send `driver`, so this card does not appear.
- `order-track-map.tsx` — static “Map coming soon” placeholder. No coordinates.
- `cancel-order-dialog.tsx` — present in the tree; the live order show page does not mount it. There is no cancel route.

List payload per order: `orderNumber`, `products` (name + quantity strings), `priceLabel`, `status`, `paymentStatus`, `date`. No `id`, no `image`, no branch.

Detail payload: `orderNumber`, `status`, `paymentStatus`, `date`, `products` (`name`, `quantityLabel`, `priceLabel`), `subtotalLabel`, `deliveryFeeLabel`, `totalLabel`, `shippingAddress` (`name`, `lines`, `mobile`), `timeline`. No branch, fulfillment, or driver fields.

What the customer can see: their own orders, public order number, product names and prices, shipping snapshot, master status, and a coarse timeline. What they cannot see: branch, fulfillment split, driver, internal shipping cost per branch, other customers’ orders.

Layout: customer portal uses storefront header/footer plus the account sidebar (`Dashboard`, `Orders`, and the rest of the account nav).

---

## 7. Branch Module

Routes (`routes/branch.php`, prefix `branch/{branch}`, middleware `ensure.guard:branch`, `resolve.branch`, permission `manage-branch-orders`):

| Method | Path | Name | Action |
| --- | --- | --- | --- |
| GET | `/branch/{branch}/orders` | `branch.orders.index` | Fulfillment DataTable for this branch only. |
| GET | `/branch/{branch}/orders/{fulfillment}` | `branch.orders.show` | One fulfillment. URL segment is the fulfillment **database id**. |
| PUT | `/branch/{branch}/orders/{fulfillment}/status` | `branch.orders.status` | Any fulfillment status. |
| POST | `/branch/{branch}/orders/{fulfillment}/paid` | `branch.orders.paid` | Sets `paid_at`. No request body. |

Controller: `App\Http\Controllers\Branch\OrderController`.

`resolveForBranch()` reloads the fulfillment with `where branch_id = current branch` and 404s otherwise. Staff cannot list or open another branch’s fulfillments.

DataTable: `BranchOrderFulfillmentDataTable`. Base query `where branch_id = current`. Eager-loads order number, customer name, payment status, and items. Search: `fulfillment_number`. Filter: `status`. Sort: fulfillment number, status, shipping cost, created at.

Pages:

- `resources/js/pages/branch/orders/index.tsx` — shared data table.
- `resources/js/pages/branch/orders/show.tsx` — fulfillment number, customer, address snapshot, items (with image), distance, shipping cost, status select, mark-paid button, fulfillment timeline.

What branch staff can do: view their fulfillments, change fulfillment status, mark that fulfillment’s collection as paid. They cannot assign a driver, cannot see other branches’ slices of the same master order as a combined workspace, and cannot override the master `payment_status` directly (it flips to `paid` only when **every** fulfillment on the order has `paid_at`).

Permission: `manage-branch-orders` (`PermissionEnum::MANAGE_BRANCH_ORDERS`), guard `branch`, group `Orders`.

---

## 8. Platform Module

Routes (`routes/platform.php`, prefix `platform`, middleware `ensure.guard:platform`, permission `manage-orders`):

| Method | Path | Name | Action |
| --- | --- | --- | --- |
| GET | `/platform/orders` | `platform.orders.index` | Every master order. |
| GET | `/platform/orders/{order}` | `platform.orders.show` | Binds `orders.id`, not `order_number`. |
| PUT | `/platform/orders/{order}/fulfillments/{fulfillment}/status` | `platform.orders.fulfillments.status` | Any fulfillment, any branch. |
| PUT | `/platform/orders/{order}/payment-status` | `platform.orders.payment-status` | Sets master `payment_status` to `unpaid`, `paid`, or `refunded`. |

Controller: `App\Http\Controllers\Platform\OrderController`.

DataTable: `OrderDataTable`. No branch constraint. Row: id, order number, customer name, `branch_count` (distinct fulfillment branches), total, status, payment status, created at. Filters: status, payment status. Search: `order_number`.

Show page (`resources/js/pages/platform/orders/show.tsx`): master header, every fulfillment card (branch name, items, shipping, status select), payment-status select.

What platform admin can do: see all orders and all fulfillments, change any fulfillment status, override master payment status at any time (this does not require every fulfillment to be marked paid, and it does not write `payments.status`). They cannot assign `driver_id`.

Permission: `manage-orders` (`PermissionEnum::MANAGE_ORDERS`), guard `platform`, group `Orders`.

Shipping rules for the fee formula are edited elsewhere (platform branch shipping settings), not on the order screen.

---

## 9. Inventory Integration

Service: `App\Services\Order\BranchAllocationService`. Distance: `HaversineDistanceCalculator` behind `App\Contracts\DistanceCalculator`.

Candidate branches:

1. Active branches (`branches.status` true), nearest first.
2. A branch with `delivery_radius_km` null can deliver anywhere.
3. A branch with a radius is a candidate only when the pin is inside that radius.
4. If every branch has a radius and the pin is outside all of them, the single nearest active branch is the only candidate (radius alone never hard-rejects).

Stock:

- For each remaining cart line, walk candidates nearest-first.
- Available quantity is `inventories.quantity` for that branch, product, and variant (or null variant), `status` true.
- Take `min(available, remaining)`. A line can split across branches.
- If anything remains, placement fails with: “One or more items in your cart are out of stock at every branch that can deliver to you.”

Fulfillment creation: one `order_fulfillments` row per branch that received at least one unit, then `order_items` for that branch’s quantities.

What does **not** happen:

- No stock reservation.
- No `inventories.quantity` decrement.
- No `inventory_transactions` row with `reference_type` = order.
- Two checkouts can allocate the same units until a later process deducts stock. That process does not exist.

`ShippingCalculator::cost()` per allocated branch:

- Use `branch_shipping_rules` when present, else `config/catalog.php` defaults (`default_shipping_base_fee`, `default_shipping_cost_per_km`, `default_shipping_minimum_charge`, `default_shipping_free_distance_km`).
- Distance at or under the free distance returns `0`.
- Otherwise `max(minimum, base + cost_per_km * distance)`.
- Null distance is treated as 0 km.
- Master `delivery_fee` is the sum of those costs.

`DeliveryMethod.fee` is ignored.

---

## 10. Payment System

Methods (`payment_gateway_settings`):

| Key | Label | Checkout behaviour |
| --- | --- | --- |
| `cod` | Cash on delivery (COD) | Offline. Unpaid until staff mark collection. |
| `pos` | POS / Card on delivery | Same. |
| `payment_link` | Payment link | Can be enabled in settings. Placement still writes an unpaid offline payment. No link is generated. |

Master `orders.payment_status`: `unpaid`, `paid`, `refunded` (`App\Enums\PaymentStatus`).

Fulfillment collection: `order_fulfillments.paid_at`. `OrderFulfillment::isPaid()` is `paid_at !== null`. There is no fulfillment payment-status column.

Who updates payment:

| Actor | Action | Effect |
| --- | --- | --- |
| Branch staff with `manage-branch-orders` | `PaymentMarkingService::markFulfillmentPaid()` | Sets this fulfillment’s `paid_at`. If every fulfillment on the order is paid, sets `orders.payment_status` to `paid`. |
| Platform admin with `manage-orders` | `PaymentMarkingService::overrideOrderPaymentStatus()` | Sets `orders.payment_status` to any `PaymentStatus`. Does not set fulfillment `paid_at`. |
| Customer | none | Chooses the method at checkout only. |
| Driver | none | Demo deliver action does not touch payment. |

`payments.status` stays `unpaid` after either of those actions. `payment_transactions` is not appended on mark-paid. Currency is `AUD`. No card gateway, webhook, or refund job exists. `refunded` is only reachable through the platform override.

---

## 11. Driver Current State

### What exists

Identity and workforce, not delivery execution:

- `users` rows with `user_type = driver` (no Spatie roles; `UserType` documents “guard + ownership checks only”).
- `driver_profiles`: `user_id` unique, `license_number` unique nullable, `vehicle_type`, `availability_status` (`available`, `busy`, `offline`, `on_break`), `notes`, `address`, `vehicle_image_path`.
- `driver_branch_assignments`: unique `(driver_id, branch_id)`, `status` (`active`, `inactive`, `suspended` via `DriverAssignmentStatus`), `assigned_by`, `started_at`, `ended_at`. This assigns a **driver to a branch**, not a driver to an order.
- `driver_rosters`: shift window per driver and branch (`start_date`, `end_date`, `start_time`, `end_time`, `days_of_week`, `status`).
- Platform permission `manage-drivers`. Branch permission `manage-branch-drivers`.
- Controllers: `Platform\DriverController`, `Platform\BranchDriverController`, `Branch\DriverController`, requests `StoreDriverRequest`, `UpdateDriverRequest`, `AssignDriverRequest`, `SyncDriverBranchesRequest`, `TransferDriverBranchRequest`.
- Service: `App\Services\Driver\DriverService`. Support: `DriverPayload`, `DriverAvailability`.
- `order_fulfillments.driver_id` nullable FK to `users`, and `OrderFulfillment::driver()`. Comment in the model: unused this phase, reserved for a future assignment phase.
- Driver portal routes under `/driver` (`routes/driver.php`), middleware `ensure.guard:driver` only. Pages exist for dashboard, route, orders, order show, order chat, history, earnings, notifications, profile, security, activity.
- `ConversationParticipantRole::Driver` and `ConversationService::openForOrder()` will add the primary fulfillment’s driver as a participant **if `driver_id` is already set** when the thread is opened.

### What does not exist

- No API or UI that writes `order_fulfillments.driver_id`.
- Driver order list, detail, route, dashboard, earnings, and history are `DemoCatalog` fixtures (`App\Support\Storefront\DemoCatalog`).
- `Driver\OrderController::deliver()` checks a 4+ digit OTP against demo data and redirects with a flash message. It does not update `order_fulfillments.status`.
- Driver chat messages are `DemoCatalog::driverOrderChatMessages()`. They are not `messages` rows and do not use Reverb.
- No driver permission enum. Drivers cannot pass `manage-orders` or `manage-branch-orders`.
- No order assignment notification, no FCM payload for a new stop, no job when a fulfillment is created.
- `driver_id` on delete is `set null`, so removing a user would clear the column, but nothing sets it today.

Driver screens that are real (profile, password, activity log, in-app notifications) are account features, not order fulfillment.

---

## 12. Conversation System

Real-time stack: Laravel Reverb. Operators use `resources/js/hooks/use-conversation-realtime.ts` and `use-conversation-inbox-realtime.ts`. Policy and HTTP routes share the same rules (`routes/channels.php` comment).

Tables: `conversations` (one per `order_id`), `conversation_participants`, `messages`, `message_attachments`, `message_reads`.

Open path: branch or platform inbox → `ConversationService::openForOrder()`.

- Idempotent because `order_id` is unique.
- Requires at least one fulfillment.
- `branch_id` is the **oldest** fulfillment’s branch (`primaryFulfillment`), even when the order was split.
- Customer is always added as `customer`.
- Driver is added only when that primary fulfillment already has `driver_id`.
- A system message is written and the inbox is notified.

Operators join when they send (`joinOperator`), mapped by user type to `branch_staff` or `platform_staff`.

Close path: `OrderConversationCloser` when the master status becomes `delivered` or `cancelled`, or an operator `POST .../conversations/{conversation}/close`.

Routes:

- Branch: `/branch/{branch}/conversations` with `manage-branch-conversations` (index, order options, store, show, messages, delivered, read, close).
- Platform: `/platform/conversations` with `manage-conversations`, plus branch options.
- Attachment download: `conversation-attachments.show` for any portal guard.
- **No customer conversation routes. No driver conversation routes against `Conversation`.**

`ConversationPolicy`: platform sees every branch; branch staff only their branch; customers and drivers may view and send only as participants, and only operators may create a thread. The policy comment states customer and driver portals are not built yet.

Future customer ↔ driver chat is **partially compatible**:

- The participant role `driver` exists.
- A driver can be attached at open time if `driver_id` is already on the primary fulfillment.
- The thread is one per order and one branch (the first fulfillment), so a split order does not get a conversation per branch or per driver.
- There is no step that adds a driver who is assigned after the thread already exists (`openForOrder` returns the existing row and does not backfill participants).
- Driver UI does not call this service.

---

## 13. Permission Matrix

`authorize()` on the order form requests returns `true`. The gate is the route middleware plus controller scoping. Customers and drivers have no order permissions.

| Feature | Customer | Branch staff | Platform admin | Driver |
| --- | --- | --- | --- | --- |
| View own / in-scope orders | Own master orders only (`customer_id`) | Own branch fulfillments (`manage-branch-orders`) | All master orders and fulfillments (`manage-orders`) | Demo list only. Not the database. |
| Place order | Yes (checkout) | No | No | No |
| Update fulfillment status | No | Yes, own branch | Yes, any branch | No (demo OTP does not persist) |
| Update master status directly | No | No (derived) | No direct field; derived from fulfillments | No |
| Assign driver to fulfillment | No | No | No | No |
| Manage driver↔branch assignment | No | `manage-branch-drivers` | `manage-drivers` | No |
| Mark fulfillment collected | No | Yes (`paid_at`) | No dedicated fulfillment paid route | No |
| Override master payment status | No | Indirectly, when all fulfillments are paid | Yes (`unpaid` / `paid` / `refunded`) | No |
| Open order conversation | No | Yes (`manage-branch-conversations`) | Yes (`manage-conversations`) | No |
| Send in a real conversation | Only if already a participant; no UI | Yes, own branch | Yes | Only if already a participant; UI is demo |
| See branch / fulfillment identity | No | Own fulfillment | All | Demo stops, not live fulfillments |

---

## 14. File Structure

### Backend — models

- `app/Models/Order.php`
- `app/Models/OrderItem.php`
- `app/Models/OrderFulfillment.php`
- `app/Models/OrderStatusHistory.php`
- `app/Models/Payment.php`
- `app/Models/PaymentTransaction.php`
- `app/Models/PaymentGatewaySetting.php`
- `app/Models/BranchShippingRule.php`
- `app/Models/DeliveryMethod.php`
- `app/Models/DeliveryTimeSlot.php`
- `app/Models/Coupon.php`
- `app/Models/CouponRedemption.php`
- `app/Models/Conversation.php`
- `app/Models/DriverProfile.php`
- `app/Models/DriverBranchAssignment.php`
- `app/Models/DriverRoster.php`

### Backend — enums

- `app/Enums/OrderStatus.php`
- `app/Enums/FulfillmentStatus.php`
- `app/Enums/PaymentStatus.php`
- `app/Enums/ConversationStatus.php`
- `app/Enums/ConversationParticipantRole.php`
- `app/Enums/PermissionEnum.php` (`MANAGE_ORDERS`, `MANAGE_BRANCH_ORDERS`, conversation and driver permissions)
- `app/Enums/DriverAssignmentStatus.php`
- `app/Enums/DriverAvailabilityStatus.php`
- `app/Enums/DriverVehicleType.php`
- `app/Enums/DriverRosterStatus.php`

### Backend — services

- `app/Services/Storefront/CheckoutService.php`
- `app/Services/Storefront/CartService.php`
- `app/Services/Storefront/CouponService.php`
- `app/Services/Order/BranchAllocationService.php`
- `app/Services/Order/HaversineDistanceCalculator.php`
- `app/Services/Order/ShippingCalculator.php`
- `app/Services/Order/FulfillmentStatusService.php`
- `app/Services/Order/OrderStatusAggregator.php`
- `app/Services/Order/PaymentMarkingService.php`
- `app/Services/Conversation/ConversationService.php`
- `app/Services/Conversation/OrderConversationCloser.php`
- `app/Services/Driver/DriverService.php`
- `app/Services/Application/PaymentGatewaySettingsService.php`
- `app/Contracts/DistanceCalculator.php`

### Backend — HTTP

- `app/Http/Controllers/Storefront/CheckoutController.php`
- `app/Http/Controllers/Customer/OrderController.php`
- `app/Http/Controllers/Branch/OrderController.php`
- `app/Http/Controllers/Platform/OrderController.php`
- `app/Http/Controllers/Driver/OrderController.php` (demo)
- `app/Http/Controllers/Driver/DashboardController.php` (demo orders)
- `app/Http/Controllers/Driver/RouteController.php` (demo)
- `app/Http/Controllers/Driver/EarningsController.php` (demo)
- `app/Http/Controllers/Branch/ConversationController.php`
- `app/Http/Controllers/Platform/ConversationController.php`
- `app/Http/Requests/Storefront/PlaceOrderRequest.php`
- `app/Http/Requests/Storefront/QuoteShippingRequest.php`
- `app/Http/Requests/Storefront/ApplyCouponRequest.php`
- `app/Http/Requests/Order/UpdateFulfillmentStatusRequest.php`
- `app/Http/Requests/Order/MarkOrderPaymentRequest.php`
- `app/Policies/ConversationPolicy.php`
- `app/DataTables/OrderDataTable.php`
- `app/DataTables/BranchOrderFulfillmentDataTable.php`

No `app/Actions` classes own order placement. No `app/Jobs` or `app/Notifications` classes reference orders. No `app/Policies/OrderPolicy.php`.

### Backend — routes

- `routes/public.php` — checkout
- `routes/customer.php` — customer orders
- `routes/branch.php` — branch orders and conversations
- `routes/platform.php` — platform orders and conversations
- `routes/driver.php` — driver portal (demo order actions)
- `routes/channels.php` — conversation channels
- `routes/conversations.php` — attachment download

### Frontend — pages

- `resources/js/pages/storefront/checkout.tsx`
- `resources/js/pages/storefront/checkout-confirmation.tsx`
- `resources/js/pages/customer/orders/index.tsx`
- `resources/js/pages/customer/orders/show.tsx`
- `resources/js/pages/branch/orders/index.tsx`
- `resources/js/pages/branch/orders/show.tsx`
- `resources/js/pages/platform/orders/index.tsx`
- `resources/js/pages/platform/orders/show.tsx`
- `resources/js/pages/branch/conversations/index.tsx`
- `resources/js/pages/platform/conversations/index.tsx`
- `resources/js/pages/driver/orders/index.tsx`
- `resources/js/pages/driver/orders/show.tsx`
- `resources/js/pages/driver/orders/chat.tsx`
- `resources/js/pages/driver/dashboard.tsx`
- `resources/js/pages/driver/route/index.tsx`
- `resources/js/pages/driver/earnings/index.tsx`
- `resources/js/pages/platform/settings/payment-gateway.tsx`

### Frontend — components

- `resources/js/components/storefront/checkout/checkout-form.tsx`
- `resources/js/components/storefront/checkout/checkout-order-summary.tsx`
- `resources/js/components/storefront/checkout/checkout-map-dialog.tsx`
- `resources/js/components/customer/order-list.tsx`
- `resources/js/components/customer/order-track-products.tsx`
- `resources/js/components/customer/order-track-address.tsx`
- `resources/js/components/customer/order-delivery-timeline.tsx`
- `resources/js/components/customer/order-track-map.tsx`
- `resources/js/components/customer/order-track-driver.tsx`
- `resources/js/components/customer/order-track-types.ts`
- `resources/js/components/conversation/*`
- `resources/js/components/driver/driver-order-card.tsx`
- `resources/js/components/driver/driver-order-chat-panel.tsx`
- `resources/js/components/driver/driver-dashboard-hub.tsx`
- `resources/js/hooks/use-conversation-realtime.ts`
- `resources/js/hooks/use-conversation-inbox-realtime.ts`

### Migrations

- `database/migrations/2026_09_18_100099_create_orders_table.php`
- `database/migrations/2026_09_18_100099a_create_order_fulfillments_table.php`
- `database/migrations/2026_09_18_100100_create_order_items_table.php`
- `database/migrations/2026_09_18_100110_create_order_status_histories_table.php`

Payment, coupon, delivery-method, shipping-rule, inventory, conversation, and driver-profile migrations live beside these and were not renamed in this audit. The live PostgreSQL schema in section 2 is the source of truth for columns and foreign keys.

### Tests that lock current behaviour

- `tests/Feature/Storefront/StorefrontCheckoutTest.php`
- `tests/Feature/Customer/CustomerOrderTest.php`
- `tests/Feature/Branch/BranchOrderTest.php`
- `tests/Feature/Platform/PlatformOrderTest.php`
- `tests/Unit/Services/Order/BranchAllocationServiceTest.php`

### Project rules already recorded

- `.ai/rules/order.md` — null delivery radius means deliver anywhere.
- `.ai/rules/storefront-checkout.md` — delivery fee is distance-based, not `DeliveryMethod.fee`.
- `.ai/rules/js-pages-storefront.md` — confirmation URL uses `order_number`.
- `.ai/rules/components-customer.md` — customer list has no product image; customer order URL uses `order_number`.
- `.ai/rules/pagescomponents-storefront-checkout.md` — checkout posts a single `name`.

---

## 15. Current Limitations

- **No inventory deduction or reservation** after a successful allocation. Stock can be oversold.
- **`orders.status = confirmed` is unused** by placement and by the aggregator.
- **No status transition graph.** Any fulfillment status can be selected from any other status, including jumping to `delivered` or reopening a terminal fulfillment. The service only skips a no-op when the value is unchanged.
- **Payment row is not updated** when staff mark an order paid. `payments.status` can remain `unpaid` while `orders.payment_status` is `paid`.
- **No online payment, payment link generation, webhook, or refund workflow.** `refunded` is a manual platform override.
- **Tax is always 0.**
- **Customer cancel is not implemented.** The dialog component is unused and there is no route.
- **Customer map is a placeholder.** No live tracking.
- **Customer order detail does not show a driver** because the controller never sends one.
- **Platform and branch order URLs still use numeric ids** (`/platform/orders/{id}`, `/branch/{branch}/orders/{fulfillmentId}`). Only the customer order page and checkout confirmation use `order_number`.
- **Driver assignment is a column only.** `driver_id` is never written.
- **Driver order UX is demo data** (dashboard, route, orders, chat, deliver, earnings, history).
- **No driver notification** when an order or fulfillment is created.
- **Conversations are not opened at checkout.** They are one per order, pinned to the oldest fulfillment’s branch, and do not gain a driver if `driver_id` is set later.
- **No customer conversation UI**, even though the policy allows a participant to view and send.
- **Split orders are invisible to the customer** by design, and branch staff only see their own slice.
- **No order domain events, jobs, or mail/FCM** for placement, status change, or payment.

---

## 16. Future Driver Assignment Requirements

Not implemented. Recorded here so a later phase can fit the current schema.

Business rules:

- Branch staff assign a fulfillment only to a driver who has an active `driver_branch_assignments` row for **that fulfillment’s branch**.
- Platform admin can assign any fulfillment to a driver, still chosen from drivers assigned to **that fulfillment’s branch** (assignment stays branch-scoped; platform is not limited to “my branch”).
- The assigned driver then sees that fulfillment on a real driver dashboard and runs the delivery.

Target flow:

```text
Order fulfillment (already created at checkout, driver_id null)
    → Assign driver (branch: own branch only; platform: any branch’s drivers)
    → order_fulfillments.driver_id
    → Driver dashboard reads real fulfillments, not DemoCatalog
    → Delivery process (status updates, collection, conversation)
```

Compatibility with what exists today:

- `order_fulfillments.driver_id` is already a nullable FK to `users` with `nullOnDelete`. The relationship `OrderFulfillment::driver()` exists. A later feature should fill this column rather than add a new assignment table, unless one driver must be historically replaced without losing the previous assignee (today there is no assignment history table).
- Branch membership already lives on `driver_branch_assignments` (`status`, unique driver+branch). Assignment UI should require an active assignment for `fulfillment.branch_id`.
- `manage-branch-orders` is the natural branch permission for “assign driver on my fulfillment.” `manage-orders` is the natural platform permission. `manage-branch-drivers` / `manage-drivers` remain workforce admin, not stop assignment.
- `FulfillmentStatusService` and `PaymentMarkingService` are the status and collection boundaries. `PaymentMarkingService`’s own comment says a later phase should narrow fulfillment-level marking to the assigned driver and leave the service boundary in place.
- Driver portal routes and pages already exist and should be re-pointed off `DemoCatalog` onto fulfillments where `driver_id` is the authenticated driver. `deliver()` must call `FulfillmentStatusService`, not a flash-only redirect.
- Conversation: `openForOrder()` adds a driver only at creation, and only from the **primary** fulfillment. A later phase needs either a per-fulfillment thread or an explicit “add participant when `driver_id` is set or changed” step. A split order with two drivers does not fit the current unique `conversations.order_id` plus single `branch_id` without a product decision.
- Do not expose `driver_id`, branch name, or fulfillment number on customer order props. The customer master order stays branch-blind.
- Do not deduct inventory inside driver assignment. Stock policy is still an open gap on placement itself.
