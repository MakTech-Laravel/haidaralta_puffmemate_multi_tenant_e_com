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
