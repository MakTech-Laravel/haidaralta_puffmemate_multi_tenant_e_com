---
paths:
    - 'app/Http/Controllers/{Platform,Branch}/**'
---

# Platform Branch

## Access admin Toast and DB roles

Access-admin mutations must use App\Support\Toast (Inertia::flash toast) for success/error/warning — never session with('success'). Last-super-admin blocks use Toast::error + redirect back. Staff role options come from Spatie DB roles for the current team, not RoleEnum::cases().

## Precognition live unique validation

Use Inertia 3 built-in Precognition (`<Form>` validate / `useForm().withPrecognition()`), not a separate `laravel-precognition-react` package. Live-validate uniqueness fields only on blur (`email`, `slug`, `code`, role `name`) with `validationTimeout={500}`. Shared targets: `StaffForm`, `RoleForm`, `BranchForm`.

## Branch status boolean; contact phone; staff search
branches.status stays boolean (Active checkbox). Branch contact phone is optional, E.164-normalized, validated with laravel-phone AU (not mobile-only). Staff indexes (platform global, nested, portal) support Scout search via ?search= on name/phone.
