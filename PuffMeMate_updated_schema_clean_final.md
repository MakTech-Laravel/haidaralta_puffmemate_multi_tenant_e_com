# PuffMeMate Updated Database Schema

``` dbml
////////////////////////////////////////////////////
// USERS & PERMISSION (SPATIE TEAM ENABLED)
////////////////////////////////////////////////////
Table users {
  id bigint [pk]
  name varchar
  email varchar [unique]
  phone varchar [unique]
  password varchar
  user_type varchar
  // platform_admin
  // platform_staff
  // branch_manager
  // branch_staff
  // driver
  // customer
  status varchar
  created_at timestamp
  updated_at timestamp
}

Table roles {
  id bigint [pk]
  name varchar
  guard_name varchar
}
Table permissions {
  id bigint [pk]
  name varchar
  guard_name varchar
}
Table model_has_roles {
  role_id bigint [ref: > roles.id]
  model_type varchar
  model_id bigint
  team_id bigint [ref: > branches.id]
}
Table model_has_permissions {
  permission_id bigint [ref: > permissions.id]
  model_type varchar
  model_id bigint
  team_id bigint [ref: > branches.id]
}
////////////////////////////////////////////////////
// BRANCH / TENANT
////////////////////////////////////////////////////
Table branches {
  id bigint [pk]
  name varchar
  slug varchar [unique]
  code varchar [unique]
  logo varchar
  phone varchar
  email varchar
  address text
  latitude decimal
  longitude decimal
  opening_time time
  closing_time time
  status varchar
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// EMPLOYEE PROFILE
////////////////////////////////////////////////////
Table employee_profiles {
  id bigint [pk]
  user_id bigint [ref: > users.id]
  employee_id varchar [unique]
  designation varchar
  department varchar
  joining_date date
  employment_status varchar
  // active
  // resigned
  // suspended
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// CUSTOMER PROFILE
////////////////////////////////////////////////////
Table customer_profiles {
  id bigint [pk]
  user_id bigint [ref: > users.id]
  date_of_birth date
  marketing_permission boolean
  preferences json
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// USER ADDRESS
////////////////////////////////////////////////////
Table addresses {
  id bigint [pk]
  user_id bigint [ref: > users.id]
  type varchar
  // home
  // office
  receiver_name varchar
  phone varchar
  address text
  city varchar
  postal_code varchar
  latitude decimal
  longitude decimal
  is_default boolean
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// USER BRANCH ASSIGNMENT
// ALL BRANCH USERS
////////////////////////////////////////////////////
Table user_branch_assignments {
  id bigint [pk]
  user_id bigint [ref: > users.id]
  branch_id bigint [ref: > branches.id]
  role varchar
  // driver
  // branch_staff
  // branch_manager
  designation varchar
  start_date date
  end_date date
  status varchar
  // active
  // transferred
  // inactive
  assigned_by bigint
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// PLATFORM USER ASSIGNMENT
////////////////////////////////////////////////////
Table user_platform_assignments {
  id bigint [pk]
  user_id bigint [ref: > users.id]
  role varchar
  // platform_admin
  // platform_staff
  designation varchar
  start_date date
  end_date date
  status varchar
  assigned_by bigint
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// USER TRANSFER HISTORY
////////////////////////////////////////////////////
Table user_transfer_histories {
  id bigint [pk]
  user_id bigint [ref: > users.id]
  from_scope varchar
  // branch
  // platform
  from_branch_id bigint [null, ref: > branches.id]
  from_role varchar
  from_designation varchar
  to_scope varchar
  // branch
  // platform
  to_branch_id bigint [null, ref: > branches.id]
  to_role varchar
  to_designation varchar
  effective_date date
  reason text
  transferred_by bigint
  created_at timestamp
}
////////////////////////////////////////////////////
// SHIFT TEMPLATE
////////////////////////////////////////////////////
Table shift_templates {
  id bigint [pk]
  owner_type varchar
  // platform
  // branch
  branch_id bigint [null, ref: > branches.id]
  name varchar
  // Morning Shift
  // Evening Shift
  // Night Shift
  start_time time
  end_time time
  is_overnight boolean
  status varchar
  created_by bigint
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// SHIFT ROSTER PERIOD
////////////////////////////////////////////////////
Table shift_roster_periods {
  id bigint [pk]
  owner_type varchar
  // platform
  // branch
  branch_id bigint [null, ref: > branches.id]
  name varchar
  start_date date
  end_date date
  status varchar
  created_by bigint
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// USER SHIFT ASSIGNMENT
// CURRENT ACTIVE SHIFT
////////////////////////////////////////////////////
Table user_shift_assignments {
  id bigint [pk]
  user_id bigint [ref: > users.id]
  branch_id bigint [null, ref: > branches.id]
  shift_template_id bigint [ref: > shift_templates.id]
  shift_roster_period_id bigint [ref: > shift_roster_periods.id]
  role_type varchar
  // driver
  // branch_staff
  // branch_manager
  // platform_staff
  start_date date
  end_date date
  status varchar
  assigned_by bigint
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// SHIFT CHANGE HISTORY
////////////////////////////////////////////////////
Table user_shift_histories {
  id bigint [pk]
  user_id bigint [ref: > users.id]
  previous_shift_id bigint [null]
  new_shift_id bigint
  branch_id bigint [null, ref: > branches.id]
  previous_start_time time
  previous_end_time time
  new_start_time time
  new_end_time time
  effective_date date
  reason varchar
  // roster_change
  // manager_request
  // employee_request
  note text
  changed_by bigint
  created_at timestamp
}
////////////////////////////////////////////////////
// DAILY GENERATED ROSTER
////////////////////////////////////////////////////
Table user_daily_rosters {
  id bigint [pk]
  user_id bigint [ref: > users.id]
  branch_id bigint [null, ref: > branches.id]
  date date
  shift_template_id bigint [ref: > shift_templates.id]
  start_time time
  end_time time
  status varchar
  // scheduled
  // completed
  // absent
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// DRIVER PROFILE
////////////////////////////////////////////////////
Table driver_profiles {
  id bigint [pk]
  user_id bigint [ref: > users.id]
  license_number varchar
  vehicle_type varchar
  availability_status varchar
}
////////////////////////////////////////////////////
// BRAND MANAGEMENT
////////////////////////////////////////////////////
Table brands {
  id bigint [pk]
  name varchar
  slug varchar [unique]
  logo varchar
  description text
  status varchar
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// CATEGORY MANAGEMENT
////////////////////////////////////////////////////
Table categories {
  id bigint [pk]
  parent_id bigint [null]
  name varchar
  slug varchar [unique]
  image varchar
  description text
  status varchar
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// PRODUCT CATALOG
////////////////////////////////////////////////////
Table products {
  id bigint [pk]
  brand_id bigint [null, ref: > brands.id]
  category_id bigint [ref: > categories.id]
  name varchar
  slug varchar [unique]
  sku varchar [unique]
  description text
  short_description text
  type varchar
  // simple
  // variable
  image varchar
  status varchar
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// PRODUCT VARIANTS
////////////////////////////////////////////////////
Table product_variants {
  id bigint [pk]
  product_id bigint [ref: > products.id]
  name varchar
  sku varchar [unique]
  barcode varchar
  attributes json
  image varchar
  weight decimal
  status varchar
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// PRODUCT IMAGES
////////////////////////////////////////////////////
Table product_images {
  id bigint [pk]
  product_id bigint [ref: > products.id]
  variant_id bigint [null, ref: > product_variants.id]
  image varchar
  sort_order integer
  created_at timestamp
}
////////////////////////////////////////////////////
// BRANCH INVENTORY
////////////////////////////////////////////////////
Table inventories {
  id bigint [pk]
  branch_id bigint [ref: > branches.id]
  product_id bigint [ref: > products.id]
  variant_id bigint [null, ref: > product_variants.id]
  quantity integer
  reserved_quantity integer
  available_quantity integer
  minimum_stock integer
  maximum_stock integer
  status varchar
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// STOCK TRANSACTION HISTORY
////////////////////////////////////////////////////
Table inventory_transactions {
  id bigint [pk]
  branch_id bigint [ref: > branches.id]
  product_id bigint [ref: > products.id]
  variant_id bigint [null, ref: > product_variants.id]
  type varchar
  // purchase
  // sale
  // return
  // adjustment
  // transfer
  quantity integer
  before_quantity integer
  after_quantity integer
  reference_type varchar
  reference_id bigint
  note text
  created_by bigint
  created_at timestamp
}
////////////////////////////////////////////////////
// STOCK TRANSFER BETWEEN BRANCHES
////////////////////////////////////////////////////
Table stock_transfers {
  id bigint [pk]
  from_branch_id bigint [ref: > branches.id]
  to_branch_id bigint [ref: > branches.id]
  transfer_number varchar [unique]
  status varchar
  // pending
  // approved
  // shipped
  // received
  // cancelled
  requested_by bigint
  approved_by bigint
  received_by bigint
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// STOCK TRANSFER ITEMS
////////////////////////////////////////////////////
Table stock_transfer_items {
  id bigint [pk]
  stock_transfer_id bigint
  product_id bigint [ref: > products.id]
  variant_id bigint [null, ref: > product_variants.id]
  quantity integer
  received_quantity integer
  status varchar
}
////////////////////////////////////////////////////
// BRANCH PRODUCT AVAILABILITY
////////////////////////////////////////////////////
// Optional but useful for customer side
Table branch_products {
  id bigint [pk]
  branch_id bigint [ref: > branches.id]
  product_id bigint [ref: > products.id]
  variant_id bigint [null, ref: > product_variants.id]
  is_available boolean
  display_status varchar
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// BRANCH PRODUCT PRICING
////////////////////////////////////////////////////
Table branch_product_prices {
  id bigint [pk]
  branch_id bigint [ref: > branches.id]
  product_id bigint [ref: > products.id]
  variant_id bigint [null, ref: > product_variants.id]
  selling_price decimal
  discount_price decimal
  cost_price decimal
  start_date date
  end_date date
  status varchar
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// PRICE HISTORY
////////////////////////////////////////////////////
Table price_histories {
  id bigint [pk]
  branch_id bigint [ref: > branches.id]
  product_id bigint [ref: > products.id]
  variant_id bigint [null, ref: > product_variants.id]
  previous_price decimal
  new_price decimal
  effective_date date
  changed_by bigint
  reason text
  created_at timestamp
}
////////////////////////////////////////////////////
// SUPPLIER MANAGEMENT
////////////////////////////////////////////////////
Table suppliers {
  id bigint [pk]
  name varchar
  company_name varchar
  phone varchar
  email varchar
  address text
  status varchar
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// PURCHASE ORDER
////////////////////////////////////////////////////
Table purchase_orders {
  id bigint [pk]
  branch_id bigint [ref: > branches.id]
  supplier_id bigint
  purchase_number varchar [unique]
  status varchar
  // draft
  // ordered
  // received
  // cancelled
  subtotal decimal
  total_amount decimal
  created_by bigint
  created_at timestamp
  updated_at timestamp
}
////////////////////////////////////////////////////
// PURCHASE ITEMS
////////////////////////////////////////////////////
Table purchase_order_items {
  id bigint [pk]
  purchase_order_id bigint [ref: > orders.id]
  product_id bigint [ref: > products.id]
  variant_id bigint [null, ref: > product_variants.id]
  quantity integer
  unit_cost decimal
  subtotal decimal
}
////////////////////////////////////////////////////
// CART SYSTEM
////////////////////////////////////////////////////
Table carts {
 id bigint [pk]
 user_id bigint
 branch_id bigint [ref: > branches.id]
 status varchar
 // active
 // converted
 // abandoned
 created_at timestamp
 updated_at timestamp
}
Table cart_items {
 id bigint [pk]
 cart_id bigint
 product_id bigint [ref: > products.id]
 variant_id bigint [null, ref: > product_variants.id]
 quantity integer
 price_snapshot decimal
 created_at timestamp
 updated_at timestamp
}
////////////////////////////////////////////////////
// WISHLIST
////////////////////////////////////////////////////
Table wishlists {
 id bigint [pk]
 user_id bigint
 created_at timestamp
 updated_at timestamp
}
Table wishlist_items {
 id bigint [pk]
 wishlist_id bigint
 product_id bigint [ref: > products.id]
 variant_id bigint [null, ref: > product_variants.id]
 created_at timestamp
}
////////////////////////////////////////////////////
// ORDERS
////////////////////////////////////////////////////
Table orders {
 id bigint [pk]
 order_number varchar [unique]
 customer_id bigint
 branch_id bigint [ref: > branches.id]
 address_id bigint
 status varchar
 // pending
 // confirmed
 // processing
 // ready_for_delivery
 // shipped
 // delivered
 // cancelled
 fulfillment_status varchar
 // pending
 // assigned
 // completed
 subtotal decimal
 discount decimal
 delivery_fee decimal
 tax decimal
 total_amount decimal
 payment_status varchar
 // unpaid
 // paid
 // refunded
 payment_method varchar
 // cod
 // card
 // wallet
 created_at timestamp
 updated_at timestamp
}
////////////////////////////////////////////////////
// ORDER ITEMS
////////////////////////////////////////////////////
Table order_items {
 id bigint [pk]
 order_id bigint [ref: > orders.id]
 product_id bigint [ref: > products.id]
 variant_id bigint [null, ref: > product_variants.id]
 product_name_snapshot varchar
 sku_snapshot varchar
 quantity integer
 unit_price decimal
 subtotal decimal
 created_at timestamp
}
////////////////////////////////////////////////////
// ORDER STATUS HISTORY
////////////////////////////////////////////////////
Table order_status_histories {
 id bigint [pk]
 order_id bigint [ref: > orders.id]
 old_status varchar
 new_status varchar
 comment text
 changed_by bigint
 created_at timestamp
}
////////////////////////////////////////////////////
// STOCK RESERVATION
////////////////////////////////////////////////////
Table stock_reservations {
 id bigint [pk]
 order_id bigint [ref: > orders.id]
 branch_id bigint [ref: > branches.id]
 product_id bigint [ref: > products.id]
 variant_id bigint [null, ref: > product_variants.id]
 quantity integer
 status varchar
 created_at timestamp
}
////////////////////////////////////////////////////
// DELIVERY ASSIGNMENT
////////////////////////////////////////////////////
Table delivery_assignments {
 id bigint [pk]
 order_id bigint [ref: > orders.id]
 branch_id bigint [ref: > branches.id]
 driver_id bigint
 assigned_by bigint
 status varchar
 // assigned
 // accepted
 // picked_up
 // delivered
 // cancelled
 assigned_at timestamp
 accepted_at timestamp
 picked_up_at timestamp
 completed_at timestamp
 created_at timestamp
}
////////////////////////////////////////////////////
// DRIVER DELIVERY HISTORY
////////////////////////////////////////////////////
Table driver_delivery_histories {
 id bigint [pk]
 driver_id bigint
 order_id bigint [ref: > orders.id]
 branch_id bigint [ref: > branches.id]
 status varchar
 note text
 created_at timestamp
}
////////////////////////////////////////////////////
// DRIVER LIVE LOCATION
////////////////////////////////////////////////////
Table driver_locations {
 id bigint [pk]
 driver_id bigint
 order_id bigint [null, ref: > orders.id]
 latitude decimal
 longitude decimal
 accuracy decimal
 speed decimal
 created_at timestamp
}
////////////////////////////////////////////////////
// DELIVERY SETTINGS
////////////////////////////////////////////////////
Table delivery_zones {
 id bigint [pk]
 branch_id bigint [ref: > branches.id]
 name varchar
 area text
 delivery_fee decimal
 status varchar
 created_at timestamp
 updated_at timestamp
}
////////////////////////////////////////////////////
// PAYMENT SYSTEM
////////////////////////////////////////////////////
Table payments {
 id bigint [pk]
 order_id bigint [ref: > orders.id]
 gateway varchar
 // stripe
 // paypal
 // cod
 transaction_id varchar
 amount decimal
 currency varchar
 status varchar
 paid_at timestamp
 created_at timestamp
}
////////////////////////////////////////////////////
// PAYMENT TRANSACTION LOG
////////////////////////////////////////////////////
Table payment_transactions {
 id bigint [pk]
 payment_id bigint [ref: > payments.id]
 event_type varchar
 // initiated
 // success
 // failed
 // refund
 response json
 created_at timestamp
}
////////////////////////////////////////////////////
// CUSTOMER WALLET
////////////////////////////////////////////////////
Table wallets {
 id bigint [pk]
 user_id bigint
 balance decimal
 status varchar
 created_at timestamp
 updated_at timestamp
}
Table wallet_transactions {
 id bigint [pk]
 wallet_id bigint
 type varchar
 // credit
 // debit
 amount decimal
 reference_type varchar
 reference_id bigint
 created_at timestamp
}
////////////////////////////////////////////////////
// OTP VERIFICATION
////////////////////////////////////////////////////
Table otp_records {
 id bigint [pk]
 order_id bigint [ref: > orders.id]
 type varchar
 // delivery
 // login
 code varchar
 expires_at timestamp
 verified_at timestamp
 created_at timestamp
}
////////////////////////////////////////////////////
// RETURN / REFUND
////////////////////////////////////////////////////
Table return_requests {
 id bigint [pk]
 order_id bigint [ref: > orders.id]
 customer_id bigint
 reason text
 status varchar
 // requested
 // approved
 // rejected
 // completed
 approved_by bigint
 created_at timestamp
 updated_at timestamp
}
Table return_items {
 id bigint [pk]
 return_request_id bigint
 order_item_id bigint
 quantity integer
 reason text
}
Table refunds {
 id bigint [pk]
 order_id bigint [ref: > orders.id]
 return_request_id bigint
 amount decimal
 gateway varchar
 transaction_id varchar
 status varchar
 refunded_at timestamp
}
```
