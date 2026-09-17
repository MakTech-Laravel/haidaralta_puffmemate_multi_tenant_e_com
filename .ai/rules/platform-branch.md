---
paths:
  - 'app/Http/Controllers/{Platform,Branch}/**'
  - 'app/Http/Controllers/{Platform,Branch}/*Driver*.php'
---

# Platform Branch

## Access admin Toast and DB roles

Access-admin mutations must use App\Support\Toast (Inertia::flash toast) for success/error/warning — never session with('success'). Last-super-admin blocks use Toast::error + redirect back. Staff role options come from Spatie DB roles for the current team, not RoleEnum::cases().

## Precognition live unique validation

Use Inertia 3 built-in Precognition (`<Form>` validate / `useForm().withPrecognition()`), not a separate `laravel-precognition-react` package. Live-validate uniqueness fields only on blur (`email`, `slug`, `code`, role `name`) with `validationTimeout={500}`. Shared targets: `StaffForm`, `RoleForm`, `BranchForm`.

## Branch status boolean; contact phone; staff search
branches.status stays boolean (Active checkbox). Branch contact phone is optional, E.164-normalized, validated with laravel-phone AU (not mobile-only). Staff indexes (platform global, nested, portal) support Scout search via ?search= on name/phone.

## Atomic branch create with first super-admin
Platform branch create is atomic: StoreBranchRequest requires nested super_admin (name/phone/password; email optional) and BranchService::createWithSuperAdmin runs branch + roles + first super-admin in one DB transaction, then redirects to show. Edit is details-only (full-width BranchForm). create-super-admin is recovery-only when needs_super_admin (create-new, no Skip). Promote/assign more admins via staff screens, not the create form.

## Branch coords via map dialog picker
Branch create/edit coords: Address then Map location with Set on map (BranchLocationPicker dialog) plus optional manual Latitude|Longitude. No inline live map on the form. Future Mapbox/Google only inside the dialog body; onConfirm writes latitude/longitude. Phase 1 confirm stays disabled until a provider can set a pin.

## Driver CRUD surfaces
Platform /platform/drivers is the full driver admin (create requires ≥1 branch_ids). Nested platform.branches.drivers and branch.drivers auto-assign the current branch on create and can assign existing drivers. Rosters require an active assignment for that branch. Overlap is per driver: date ranges overlap AND weekdays intersect AND times overlap. Roster forms use start/end dates + Mon–Sun day chips. No parcel/GPS/chat yet.

## Stock pages share a product/branch/locked query contract
Manage stock, Import stock, and Stock transactions exist on both portals and share the query contract `product`, `branch`, `locked` (`product|branch|both`), resolved by `App\Support\StockPageContext`. Locked selections 404 when the id or slug does not resolve.
The branch portal always forces `locked` to include `branch` (`StockPageContext::fromRequest($request, $branch)`), and import writes assert `branch_ids` match the current branch.
When a product is missing from a picker, the Add product link carries `return_to` = current URL; `CatalogReturnTo::afterProductCreate` redirects back with `?product={id}` appended.
`GET stock/manage` renders the pair-picker workspace. When both IDs resolve, load `BranchProductShowPayload::product` plus adjustment/discount types — do not load `::transactions`. Stock/price writes stay on nested `platform.branches.products.inventory|prices` and `branch.products.inventory|prices`. After save, `back()` returns to `stock/manage?…`.
Old `GET platform.branches.products.show` 302s to `stock/manage?branch=&product=&locked=both`. `GET branch.products.show` renders catalog details (`branch/catalog/products/show`) — listing only, no Branch network. `stock/price` and `/discounts` still redirect through `App\Support\ProductManageRedirect` (pair → `stock.manage` with query; platform product-only → Manage branches; otherwise products index). Do not restore `POST stock/manage`.

## Discounts are not a Stock child
Discounts are edited on the product manage Inventory & pricing card. Do not add a DiscountController, `/discounts` workspace, or discount actions on StockController. Old `/discounts` and `/stock/discount` URLs redirect through `ProductManageRedirect`.

## Stock transaction filters paginate branch and product options
Do not embed the full branch or product catalogs in `InventoryTransactionDataTable` filterOptions. Branch and product filters are shadcn Popover+Command comboboxes that fetch `stock/branch-options` and `stock/product-options` (page, per_page, filter[search]) and append on scroll. Keep only `types` plus the currently selected branch/product labels on the table payload. Row names link to the matching show page (`branch_url`, `product_url`, `created_by_url`).
