---
paths:
    - 'app/Http/Controllers/{Platform,Branch}/**'
---

# Platform Branch

## Access admin Toast and DB roles

Access-admin mutations must use App\Support\Toast (Inertia::flash toast) for success/error/warning — never session with('success'). Last-super-admin blocks use Toast::error + redirect back. Staff role options come from Spatie DB roles for the current team, not RoleEnum::cases().
