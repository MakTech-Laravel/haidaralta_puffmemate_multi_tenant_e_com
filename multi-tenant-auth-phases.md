# Multi-Tenant Auth System — Phase-by-Phase Implementation Plan

**Project:** haidaralta-multi-tenant-e-com
**Stack:** Laravel 13, Inertia 3, React starter kit, Spatie laravel-permission (teams on), PostgreSQL

## Locked-in decisions (don't relitigate these mid-build)

- Single `users` table, `user_type` column (`platform` | `branch` | `driver` | `customer`) — backed by a `UserType` PHP enum (Phase 1)
- 4 separate auth guards (`platform`, `branch`, `driver`, `customer`), all on the same `users` provider
- One shared `/login` page (React/Inertia); backend looks up the account by email, reads its `user_type`, authenticates on the matching guard, redirects to that portal's dashboard
- Spatie `teams` enabled, `team_foreign_key` = `branch_id`; branch users get their `branch_id` as permission-team scope; **platform uses a reserved team id `0`** (not `null` — see "Team scoping model" below)
- Super-admin bypass (both platform and branch scope) = role-name check `'super-admin'` via `Gate::before`, team-aware
- Driver and customer: no roles/permissions, just guard + ownership checks
- Roles and permissions are backed by `RoleEnum` and `PermissionEnum` PHP enums (Phase 2) rather than raw strings, so IDEs and static analysis catch typos
- Scope for the auth phases (1–7): auth, forgot/reset password, redirect-by-type, route protection, 4 basic protected dashboard shells with real frontend permission infrastructure. Phase 8 adds admin CRUD UI for roles/permissions/branch staff. Phase 9 (deferred) ports unrelated shared UI infra.

Run every phase in order — each one assumes the previous is done and tested. Don't let the agent skip the test step to "save time"; the whole point of this system is airtight isolation between portals, and that's exactly the kind of thing that silently breaks without tests.

## Team scoping model — reserved id `0`, not `null`

Phases 1 and 2 (below) were originally implemented with `branch_id = null` for platform-scope roles. That was reworked after auditing a reference multi-tenant project (`blake_lms_tenant`) that uses the same `spatie/laravel-permission` teams feature at a deeper nesting level (platform → school → branch). Its `PlatformTeamResolver` reserves team id `0` for the platform scope instead of `null`, for a concrete reason:

**PostgreSQL does not treat `NULL = NULL` as equal in a unique constraint.** A unique index on `(branch_id, name, guard_name)` with `branch_id` nullable will silently allow two `('platform-admin', NULL, 'platform')` rows to exist — a duplicate-role bug with no database-level guard against it. Using the concrete value `0` avoids this and lets Spatie's original composite-primary-key migration schema stay untouched (no patched pivot tables), which is safer against future package upgrades.

**Practical effect on Phases 1–2 (if you already ran them with `null`):**

- Revert the pivot-table migration patch back to Spatie's stock schema (composite primary key `(branch_id, permission_id/role_id, model_id, model_type)`, `branch_id` NOT NULL when teams are enabled)
- Add `app/Support/PlatformTeamResolver.php` (extends `Spatie\Permission\DefaultTeamResolver`), defaulting `getPermissionsTeamId()` to `0` when unset, and set `'team_resolver' => \App\Support\PlatformTeamResolver::class` in `config/permission.php`
- Platform users get `branch_id = 0` conceptually for role/permission scoping — but the **`users.branch_id` column itself stays `null` for platform users** (a platform user does not belong to a branch record; `0` is purely the Spatie team-scoping sentinel, resolved automatically by `PlatformTeamResolver` whenever `setPermissionsTeamId()` hasn't been called for the request, e.g. on platform/driver/customer routes)
- Seeders assign platform roles while the resolver's default (`0`) is active, instead of explicitly calling `setPermissionsTeamId(null)`
- `branches.id` is a PostgreSQL `bigserial` starting at 1, so `0` never collides with a real branch

## Preamble — prepend this to every master prompt below (Plan Mode + MCP tooling)

Paste this block at the very top of whichever phase prompt you're running in Cursor, before the
phase-specific instructions. It puts the agent in plan-first mode and tells it which MCP tools to
lean on, so it verifies real project state instead of guessing.

```
You are running in Plan Mode. Do NOT write or edit any code yet.

STEP 1 — Read context first.
Before proposing anything, check the actual current state of this Laravel project relevant to
this phase — existing routes, config, migrations, models, installed packages — using MCP tools
where available (see list below) rather than assuming from general Laravel knowledge or from
what earlier phases were SUPPOSED to have produced. If something from a prior phase looks
missing or different than expected, tell me before proceeding.

STEP 2 — Produce a plan only, no code.
List every file you will create or modify for this phase, in the order you'll do it, with a
one-line reason per file. Note any command you'll need to run (composer/artisan/npm). Flag any
assumption about this project's current state that you could not verify. Keep it a plan, not an
implementation — no code blocks in this step.

STEP 3 — Stop and wait for my explicit approval before writing any code.
Do not proceed past the plan until I confirm it.

STEP 4 — Implement exactly what was approved.
If you need to deviate from the approved plan once you're in the code, stop and tell me why
before continuing. Don't silently expand scope.

STEP 5 — Validate with real evidence.
Run this phase's validation checklist (given at the end of the phase-specific instructions below)
and report the ACTUAL output of commands/tests you ran — paste real terminal output, not a
summary claiming success without showing it.

MCP tools available in this environment — use them where relevant instead of relying on memory:
- laravel-boost (or equivalent Laravel MCP): inspect the real route list, migration status,
  config values, and schema. Laravel 13 changed some conventions (e.g. bootstrap/app.php
  replacing several old service-provider registrations) — confirm against this project's actual
  files, not an older mental model. If it can run artisan commands directly, use it to run
  migrations, `route:list`, and the test suite, and report the real output.
- context7 (or equivalent docs MCP): pull current documentation for Laravel 13, Inertia 3, and
  spatie/laravel-permission (especially its teams feature) before implementing anything
  version-sensitive — don't rely on training data for API shapes that may have changed.
- shadcn MCP (or equivalent UI component MCP): if this project's starter kit uses shadcn/ui, use
  it to scaffold form/card/nav components consistent with the starter kit's existing design
  tokens rather than hand-rolling one-off markup.
- Cursor browser tool (or equivalent browser MCP): after implementing, actually drive the flow in
  a real browser — log in as each seeded test user, confirm the redirect target, attempt a
  cross-portal URL, submit the forgot-password form — and show me what you observed, not just
  "tests pass." This catches UI-level issues a Pest/PHPUnit suite won't.

If a tool isn't connected, say so rather than guessing what it would have shown you.
```

---

## Phase 1 — Foundation: packages, config, schema, models

**Goal:** database and model layer ready, using the reserved-team-id-`0` scoping model and a `UserType` enum. No auth flow yet.

### Master prompt for Cursor

```
You are working in a Laravel 13 project (React + Inertia starter kit already installed,
PostgreSQL already connected). This project previously ran a version of Phase 1 that used
`branch_id = null` for platform-scope Spatie roles; this rework switches to a reserved team id
`0` instead (see "Team scoping model" at the top of this document for why). Do the following,
in order:

1. Install spatie/laravel-permission via composer and publish its config + migration, if not
   already installed. If the pivot-table migration was previously patched to make `branch_id`
   nullable with a surrogate primary key, REVERT it to Spatie's stock schema (composite primary
   key across team key + pivot key + model id + model type, `branch_id` NOT NULL when teams are
   enabled).
2. In config/permission.php, set 'teams' => true and 'team_foreign_key' => 'branch_id'.
3. Create app/Support/PlatformTeamResolver.php extending
   Spatie\Permission\DefaultTeamResolver, overriding getPermissionsTeamId() to return the
   parent's value if set, otherwise 0 (the reserved platform team id). Register it in
   config/permission.php as 'team_resolver' => \App\Support\PlatformTeamResolver::class.
4. Create app/Enums/UserType.php as a string-backed PHP enum with cases PLATFORM ('platform'),
   BRANCH ('branch'), DRIVER ('driver'), CUSTOMER ('customer'). Add a guard(): string method
   returning the matching guard name (identical to the value for this project, since guard names
   and user types share the same 4 strings) and a label(): string method for human-readable
   display.
5. Create a migration to add to the existing users table:
   - user_type: string, not nullable, no default (values come from UserType enum: 'platform',
     'branch','driver','customer')
   - branch_id: foreignId, nullable, constrained references branches, nullOnDelete (this column
     is null for platform/driver/customer users; it holds the actual owning branch only for
     branch-type users — it is NOT the same as the Spatie team-scoping sentinel `0`, which is
     resolved separately by PlatformTeamResolver and never stored on the user row)
   - is_active: boolean, default true
   Do NOT drop or rename existing columns from the starter kit's users table.
6. Create a `branches` table migration: id, name (string), slug (string, unique), address (text, nullable),
   latitude (decimal 10,7 nullable), longitude (decimal 10,7 nullable), is_active (boolean default true), timestamps.
   Create the branches table BEFORE the users-table alteration migration above, since the
   foreign key depends on it.
7. Register 4 additional guards in config/auth.php — platform, branch, driver, customer — all
   driver=session, provider=users. Keep the default 'web' guard untouched. Add matching entries
   under 'passwords' for each of the 4 guards (provider=users, table=password_reset_tokens).
8. Create app/Models/Branch.php with $fillable for the columns above, and a users() hasMany relation.
9. Update app/Models/User.php:
   - use Spatie\Permission\Traits\HasRoles
   - add branch_id and user_type and is_active to the fillable set (use whatever fillable
     mechanism this project's User model already uses — check before assuming a $fillable
     property vs a PHP attribute)
   - cast user_type to the UserType enum
   - add a branch() belongsTo(Branch::class) relation
   - add boolean helper methods isPlatform(), isBranch(), isDriver(), isCustomer() that check
     the user_type enum (delegate to $this->user_type === UserType::X)
   - add a guardName(): string method that returns $this->user_type->guard() — this will be
     used everywhere else in the app to avoid hardcoding guard names
10. Run `php artisan migrate` against the connected PostgreSQL database and confirm it succeeds
    with no errors. Show me the output.

Validation before you consider this phase done:
- `php artisan migrate:status` shows all migrations ran
- `php artisan tinker` → `App\Models\User::first()` does not error (table structure is valid even if empty)
- `php artisan tinker` → `app(App\Support\PlatformTeamResolver::class)` resolves without a bound
  team id and, when queried via `app(Spatie\Permission\PermissionRegistrar::class)->getPermissionsTeamId()`
  with nothing explicitly set, returns `0`
- config/auth.php has exactly 5 session guards: web, platform, branch, driver, customer, all on the users provider
- Do not write any controllers, routes, or React pages in this phase — foundation only.
```

**You confirm before moving on:** migrations ran clean, `branches` table exists, `users` table has the 3 new columns, `config/auth.php` has all 5 guards, `PlatformTeamResolver` defaults to team id `0`.

---

## Phase 2 — Roles, permissions, and seed data

**Goal:** default roles/permissions exist (backed by enums), plus at least one test user per portal type to log in with.

### Master prompt for Cursor

```
Building on Phase 1 (spatie/laravel-permission installed, teams enabled with team_foreign_key
'branch_id', PlatformTeamResolver reserving team id 0 for platform scope, users table has
user_type/branch_id/is_active, UserType enum exists).

This rework replaces raw role/permission strings with PHP enums and replaces explicit
`setPermissionsTeamId(null)` calls with relying on PlatformTeamResolver's default of `0`.

1. Create app/Enums/PermissionEnum.php as a string-backed PHP enum covering at minimum:
   VIEW_DASHBOARD ('view-dashboard'), MANAGE_USERS ('manage-users'), MANAGE_ROLES
   ('manage-roles'), MANAGE_BRANCHES ('manage-branches') for platform scope; VIEW_BRANCH_DASHBOARD
   ('view-branch-dashboard'), MANAGE_BRANCH_PRODUCTS ('manage-branch-products'),
   MANAGE_BRANCH_ORDERS ('manage-branch-orders'), MANAGE_BRANCH_STAFF ('manage-branch-staff')
   for branch scope. Add guard(): string (returns 'platform' for the first 4 cases, 'branch' for
   the last 4), group(): string (a short grouping label for future admin UI), and a static
   forGuard(string $guard): array helper returning all cases for that guard.

2. Create app/Enums/RoleEnum.php as a string-backed PHP enum with cases SUPER_ADMIN
   ('super-admin'), ADMIN ('admin'), MANAGER ('manager'), STAFF ('staff'). These role NAMES are
   shared across both the platform and branch guards (same string, different guard_name /
   team scope) — the enum itself does not encode scope, only the canonical name.

3. Create database/seeders/RolesAndPermissionsSeeder.php that:
   - Creates all 8 permissions via Spatie's Permission::firstOrCreate using
     PermissionEnum::cases(), keyed by name + guard() (guard_name aware — platform
     permissions use guard 'platform', branch permissions use guard 'branch')
   - Creates the 4 RoleEnum roles on guard 'platform', relying on PlatformTeamResolver's default
     team id (0) — do NOT call setPermissionsTeamId() for this; simply do not set a team id and
     let the resolver supply 0.
   - Exposes a public seedBranchRolesForBranch(Branch $branch) method that creates the 4
     RoleEnum roles on guard 'branch' scoped explicitly to that branch's id via
     setPermissionsTeamId($branch->id), then resets the team context back to unset (so the
     resolver's default of 0 takes over again) when done.
   - Gives 'super-admin' roles (both guards) no permissions at all — they bypass via Gate::before,
     defined in Phase 5, not via permission assignment.
   - Gives 'admin' most permissions, 'manager' a mid-tier subset, 'staff' minimal — use your
     judgment, this is just seed data to develop against, not final business rules.

4. Create database/seeders/BranchSeeder.php that creates 2 sample branches (e.g. "Downtown",
   "Uptown") with realistic slugs, and after creating each branch calls
   RolesAndPermissionsSeeder::seedBranchRolesForBranch() for it (branch roles cannot exist before
   the branch row does, so this ordering is required even though RolesAndPermissionsSeeder runs
   first for platform roles).

5. Create database/seeders/UserSeeder.php that creates exactly one test user per type, all
   with password 'password' (hashed), all is_active = true:
   - platform@test.com — user_type=platform, branch_id=null, assign role RoleEnum::SUPER_ADMIN
     on guard 'platform' while the team context is unset (resolver supplies 0)
   - branch@test.com — user_type=branch, branch_id = first seeded branch's id, assign role
     RoleEnum::SUPER_ADMIN on guard 'branch' scoped to that branch's team id
   - driver@test.com — user_type=driver, branch_id=null, no role needed
   - customer@test.com — user_type=customer, branch_id=null, no role needed

6. Wire all three seeders into DatabaseSeeder::run() in the correct order (permissions/platform
   roles → branches (+ branch roles) → users, since users depends on branches existing).

7. Run `php artisan migrate:fresh --seed` and show me the output. Then confirm via tinker that
   `App\Models\User::where('email','platform@test.com')->first()->hasRole(RoleEnum::SUPER_ADMIN->value)`
   returns true with NO explicit team id set (i.e. do not call setPermissionsTeamId at all before
   this check — the resolver's default of 0 must be what makes it pass).

Validation:
- migrate:fresh --seed runs with zero errors
- 4 users exist, one per user_type
- 2 branches exist
- platform@test.com has role super-admin on guard platform, resolved via the default team id (0),
  with no explicit setPermissionsTeamId(null) call anywhere in the codebase
- branch@test.com has role super-admin on guard branch scoped to their branch's team id
- A unique index on (branch_id, name, guard_name) in the roles table would reject a duplicate
  platform-scope role name — confirm this is enforceable (i.e. the migration was reverted to
  Spatie's stock schema per the Team scoping model section, not the earlier nullable-pivot patch)
```

---

## Phase 3 — Unified login, guard resolution, redirect by type

**Goal:** one `/login` page; after submit, user lands on the correct portal dashboard route.

### Master prompt for Cursor

```
Building on Phases 1–2. Do NOT use the starter kit's default auth scaffolding for login logic —
replace it, since we need custom guard resolution. Reuse its React UI components/styling where
convenient.

1. Backend: create app/Http/Controllers/Auth/LoginController.php (or a Form Request-based
   equivalent matching the starter kit's conventions) with:
   - GET /login → renders a single Inertia page resources/js/Pages/Auth/Login.tsx (or .jsx, match
     the starter kit's existing extension). If already authenticated on ANY of the 4 guards,
     redirect straight to that portal's dashboard instead of showing the form again.
   - POST /login → validate email + password via a Form Request (LoginRequest) with sensible
     rules and a rate limiter (max 5 attempts per email+ip per minute, using Laravel's built-in
     RateLimiter — throw a 429/validation error with a clear message when exceeded).
   - Look up the user by email FIRST (without knowing the type in advance), read user_type off
     that record, then attempt Auth::guard($user->guardName())->attempt(...) with the submitted
     credentials. If the user is not found, or is_active is false, or credentials are wrong,
     return the same generic "these credentials do not match our records" error either way —
     do not leak whether the email exists.
   - On success: regenerate the session, then redirect (Inertia::location or a normal redirect,
     match starter-kit convention) to:
       platform → /platform/dashboard
       branch   → /branch/{branch-slug}/dashboard   (look up the user's branch relation for the slug)
       driver   → /driver/dashboard
       customer → /dashboard
   - POST /logout → log out of the CURRENT guard only (determine which guard is active by
     checking each of the 4 guards for an authenticated user), invalidate session, regenerate
     CSRF token, redirect to /login.

2. Frontend: resources/js/Pages/Auth/Login.tsx — a single form (email, password, remember me
   checkbox, submit button), using Inertia's useForm. Show validation/auth errors inline.
   No user-type selector on this form — the backend figures it out.

3. Routes: add these to routes/web.php (or a routes/auth.php you require from web.php, match
   starter kit convention) OUTSIDE of any guard middleware group, since login itself is
   guard-agnostic until credentials are checked.

Validation — do this manually or write quick feature tests (proper test suite comes in Phase 7,
but sanity-check now):
- Logging in as platform@test.com lands on /platform/dashboard (route can 404 for now, that's fine,
  just confirm the redirect target)
- Logging in as branch@test.com lands on /branch/{their-slug}/dashboard
- Logging in as driver@test.com lands on /driver/dashboard
- Logging in as customer@test.com lands on /dashboard
- Wrong password shows a generic error, not "wrong password" specifically
- Submitting a non-existent email shows the SAME generic error (no user enumeration)
- 6th rapid login attempt within a minute is rate-limited
```

---

## Phase 4 — Forgot password / reset password (per guard)

**Goal:** each portal can recover a forgotten password, still through unified entry points.

### Master prompt for Cursor

```
Building on Phase 3. Laravel's default password broker is per-guard-provider pair — we already
registered 4 broker configs in Phase 1 (config/auth.php 'passwords' → platform/branch/driver/customer,
all using the same password_reset_tokens table and users provider).

1. GET /forgot-password → Inertia page resources/js/Pages/Auth/ForgotPassword.tsx with just an
   email field.
2. POST /forgot-password → look up the user by email (same "don't leak existence" principle as
   login: always show the same "if that email exists, we've sent a link" success message,
   whether or not the account exists). If found, use Password::broker($user->guardName())->sendResetLink()
   so the correct guard's broker (and thus correct notification route back to /reset-password) is used.
   Rate-limit this endpoint too (e.g. 3 requests per email per 10 minutes) to prevent email-bombing.
3. Customize the reset notification (App\Notifications or override User::sendPasswordResetNotification)
   so the reset link points to a SINGLE shared /reset-password/{token} route regardless of guard —
   don't create 4 different reset URL patterns, keep the user experience uniform. Encode which
   guard/broker to use as a query param or infer it again from the user's user_type when the
   reset form is submitted (same "look up user first" pattern as login).
4. GET /reset-password/{token} → Inertia page resources/js/Pages/Auth/ResetPassword.tsx (token,
   email hidden fields, new password + confirmation fields).
5. POST /reset-password → validate token + email + new password (min length, confirmed) via
   Password::broker($guardResolvedFromEmail)->reset(...). On success, log the user out of any
   existing sessions ON THAT GUARD ONLY (defense in depth) and redirect to /login with a success
   flash message.

Validation:
- Request a reset for driver@test.com — confirm (via log/mail driver set to 'log' in .env for
  dev) that a reset email/log entry is generated referencing the driver guard's broker
- Submitting the reset form with a valid token successfully changes the password and the user
  can log in with the new password afterward through the normal /login flow
- Requesting a reset for a non-existent email still returns the generic success message
- An expired or already-used token shows a clear "invalid or expired" error, not a stack trace
```

---

## Phase 5 — Route protection & cross-portal isolation middleware

**Goal:** the actual security boundary — this is the phase to be most paranoid about.

### Master prompt for Cursor

```
Building on Phases 1–4. This phase enforces that a user authenticated on one guard can NEVER
reach another portal's routes, and a branch user can never reach another branch's routes.

1. Create app/Http/Middleware/EnsurePortalGuard.php — a parametrized middleware
   (e.g. `ensure.guard:platform`) that:
   - Checks Auth::guard($param)->check(). If not authenticated on that specific guard, redirect
     to /login (preserve intended URL via session so we can return here after login).
   - If authenticated on that guard, set Auth::shouldUse($param) for the rest of the request so
     Auth::user() resolves correctly downstream.
   - Also check the user's is_active flag; if false, log them out of that guard and redirect to
     /login with an error.
   Register this as route middleware alias 'ensure.guard' in bootstrap/app.php (Laravel 13 style).

2. Create app/Http/Middleware/ResolveBranchContext.php for branch routes specifically
   (domain/branch/{branch}/*):
   - Resolve the {branch} route parameter (slug) to a Branch model (404 if not found or inactive).
   - Confirm the authenticated branch-guard user's branch_id matches this branch's id — if not,
     abort(403) with a clear "you do not have access to this branch" message. Do NOT redirect
     them into their own branch silently; a 403 makes the isolation failure loud and testable.
   - Call app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($branch->id)
     so all subsequent Spatie permission checks in this request are correctly scoped.
   - Bind the resolved Branch into the request/container (e.g. app()->instance('currentBranch', $branch))
     so controllers don't need to re-look it up.

3. Set up app/Providers/AuthServiceProvider.php (or bootstrap/app.php's authorization section in
   L13) with a Gate::before() closure implementing the super-admin bypass:
   - If Auth::user()->hasRole(RoleEnum::SUPER_ADMIN->value) is true for the CURRENTLY ACTIVE guard
     (both platform and branch use the same role name, so hasRole() checks against whichever
     team id is active — 0 by default via PlatformTeamResolver on platform routes, or the branch's
     id after ResolveBranchContext runs on branch routes), return true unconditionally (full
     bypass, gate-pass style).
   - Otherwise return null (fall through to normal permission checks).
   - This must run AFTER team id is set for branch requests (order matters — Gate::before fires
     per-check at authorization time, not at middleware time, so as long as ResolveBranchContext
     runs before any authorize()/can() call in the request lifecycle, this is fine — just confirm
     middleware order in the branch route group has ResolveBranchContext before any controller logic).

4. Wire up route groups in routes/web.php:
   Route::middleware(['ensure.guard:platform'])->prefix('platform')->group(...) → platform routes
   Route::middleware(['ensure.guard:branch', 'resolve.branch'])->prefix('branch/{branch}')->group(...) → branch routes
   Route::middleware(['ensure.guard:driver'])->prefix('driver')->group(...) → driver routes
   Route::middleware(['ensure.guard:customer'])->prefix('customer')->group(...) → customer routes
   Each group should have at minimum a GET /dashboard route for now (controllers built in Phase 6).

5. Also add a small guard against a subtle hole: if a platform user is currently logged in and
   manually navigates to /branch/{any-slug}/dashboard, they must be redirected to /login (not
   just 403'd) since ensure.guard:branch will correctly fail them — confirm this explicitly, it's
   the core requirement from the spec ("platform users will never access branch/driver/customer
   dashboard routes").

Validation (be thorough here, this is the security-critical phase):
- platform@test.com can reach /platform/dashboard, gets 302→/login on /branch/*, /driver/*, /customer/*
- branch@test.com can reach /branch/{their-slug}/dashboard, gets 403 on /branch/{other-slug}/dashboard,
  gets 302→/login on /platform/*, /driver/*, /customer/*
- driver@test.com and customer@test.com are symmetric — only their own prefix works
- An unauthenticated request to any of the 4 dashboard routes redirects to /login
- Confirm Spatie's team id is correctly set during a branch request (e.g. temporarily dd() it in
  the middleware during manual testing, then remove the dd())
- platform@test.com's super-admin role passes a Gate::allows() check with NO explicit team id set
  (relying on PlatformTeamResolver's default of 0), proving the reserved-id model works end to end
```

---

## Phase 6 — Dashboards + frontend permission/tenancy infrastructure

**Goal:** something to actually look at, with real permission-aware navigation and a reusable frontend pattern for every future page — not just a static "Welcome" screen.

This phase was expanded after reviewing a reference multi-tenant project (`blake_lms_tenant`) that
solved the same problem: a `usePermission()` hook reading flat `roles[]`/`permissions[]` arrays
shared via Inertia, a data-driven navigation component that hides links the user can't use, and
one layout composition pattern reused by every portal.

### Master prompt for Cursor

```
Building on Phase 5 (routes and middleware exist and are verified working).

1. Backend — Inertia shared props: update app/Http/Middleware/HandleInertiaRequests.php (or the
   equivalent Laravel 13 file in this starter kit) to share, on every request where a user is
   authenticated on any guard:
   - auth.user: the user's own attributes PLUS a flat roles: string[] (from getRoleNames(), for
     the CURRENTLY active guard/team) and permissions: string[] (from
     getAllPermissions()->pluck('name')) and is_super_admin: bool
     (hasRole(RoleEnum::SUPER_ADMIN->value)).
   - portal: one of 'platform' | 'branch' | 'driver' | 'customer', derived from user_type, so the
     frontend layout resolver can pick the right chrome without re-deriving it from the URL.
   - branch: null outside the branch portal; otherwise { id, name, slug } for the current branch
     (from app('currentBranch') bound in Phase 5's ResolveBranchContext).
   Be precise about WHEN roles/permissions are read — they must reflect whichever Spatie team id
   is active for this request (0 for platform/driver/customer routes via PlatformTeamResolver,
   the branch's id for branch routes via ResolveBranchContext already having run).

2. Frontend — permission hook: create resources/js/hooks/use-permissions.tsx exporting
   usePermission() that reads auth.user.roles / auth.user.permissions / auth.user.is_super_admin
   from Inertia's usePage().props and exposes can(permission), canAny(permissions[]),
   canAll(permissions[]), hasRole(role), hasAnyRole(roles[]) — super-admin bypasses all can*
   checks. Create resources/js/types/permissions.ts with a PermissionKey union type generated
   from (or manually mirroring) the PermissionEnum cases from Phase 2, so permission strings used
   in the frontend are typo-checked at compile time.

3. Frontend — tenancy hook: create resources/js/hooks/use-branch.ts exporting useBranch() that
   reads the branch shared prop and throws a clear error if called outside the branch portal
   (fails loudly during development rather than silently rendering wrong data). Type it in
   resources/js/types/tenant.ts as a BranchContext interface { id, name, slug }.

4. Frontend — layouts: create one Inertia layout per portal, matching the starter kit's existing
   layout conventions:
   resources/js/Layouts/PlatformLayout.tsx
   resources/js/Layouts/BranchLayout.tsx
   resources/js/Layouts/DriverLayout.tsx
   resources/js/Layouts/CustomerLayout.tsx
   Each composes the same shell: a sidebar (built in step 5) + a content area with a top bar
   showing the portal name, the logged-in user's name/email, and a logout button (POST to
   /logout, use Inertia's router.post). Keep styling minimal/basic — reuse whatever CSS/UI kit
   the starter kit already ships with (Tailwind, shadcn, etc.) rather than introducing a new one.

5. Frontend — data-driven navigation: create resources/js/components/navigation/ with:
   - types.ts: a NavNode interface { title, href, icon?, permissions?: PermissionKey[], items?:
     NavNode[] } — permissions is an ANY-match gate (user needs at least one to see the item).
   - nav-utils.ts: filterNavNodes(nodes, canAny) that recursively drops nodes the user can't see
     and drops now-empty parent groups.
   - sidebar-nav.tsx: renders the filtered tree, calling usePermission().canAny() internally.
   Build 4 small nav arrays (one per portal, e.g. resources/js/components/platform/nav.ts) — for
   now each just needs a "Dashboard" entry gated by the matching view-dashboard /
   view-branch-dashboard permission; driver/customer have no permission gates (they have no
   roles per the locked decisions) so their nav items omit the permissions key entirely.

6. Backend controllers + routes: create matching controllers:
   app/Http/Controllers/Platform/DashboardController.php → renders resources/js/Pages/Platform/Dashboard.tsx
   app/Http/Controllers/Branch/DashboardController.php → renders resources/js/Pages/Branch/Dashboard.tsx,
     passing the current branch (from app('currentBranch')) as a prop
   app/Http/Controllers/Driver/DashboardController.php → renders resources/js/Pages/Driver/Dashboard.tsx
   app/Http/Controllers/Customer/DashboardController.php → renders resources/js/Pages/Customer/Dashboard.tsx
   Each Dashboard.tsx page wraps itself in its matching Layout (using the sidebar-nav from step 5)
   and shows a simple "Welcome, {name}" message plus (for platform/branch) the user's current
   role name(s) via usePermission().hasRole() or the shared roles[] array directly.
   Wire these controllers into the route groups defined in Phase 5 (replace the placeholder
   GET /dashboard closures with these controller actions).

7. For the branch dashboard specifically, also display the branch name/slug (via useBranch())
   so it's visually obvious which branch context you're in — useful for manually testing
   isolation later.

Validation:
- All 4 test users can log in via /login and land on a dashboard that renders without errors,
  shows their name, and (platform/branch) shows their super-admin role
- The sidebar nav renders the Dashboard link for platform/branch users and hides nothing
  incorrectly (there's only one nav item right now, but confirm the permission check actually
  runs — temporarily seed a 'staff' user without view-dashboard to confirm the link disappears,
  then remove that test user if it's not part of the real seed data)
- Logout button works and returns to /login
- Directly hitting another portal's dashboard URL while logged in still redirects/403s as per Phase 5
- useBranch() throws if you (temporarily, for testing) call it from a non-branch page — confirms
  the "fail loudly" behavior works
```

---

## Phase 7 — Automated test suite

**Goal:** lock in every guarantee above so future changes can't silently break isolation.

### Master prompt for Cursor

```
Building on Phases 1–6. Write Pest (or PHPUnit, match whatever the starter kit uses) feature
tests. Use RefreshDatabase and the seeders from Phase 2 (or lightweight factories if you prefer
faster tests — your call, but seeded roles/permissions must exist for role checks to work).

Create tests/Feature/Auth/LoginTest.php covering:
- Each of the 4 seeded users can log in via POST /login and lands on their correct dashboard URL
- Wrong password returns a generic validation error (assert the error message does NOT differ
  based on whether the email exists)
- Non-existent email returns the same generic error
- Inactive user (is_active=false) cannot log in even with correct credentials
- Rate limiting kicks in after repeated failed attempts from the same email/IP

Create tests/Feature/Auth/PasswordResetTest.php covering:
- Requesting a reset for each of the 4 user types generates a valid, guard-correct reset token
- Submitting the reset form with a valid token changes the password and the new password works
  on the next login
- Requesting a reset for a non-existent email returns the same generic success response
- An invalid/expired token is rejected with a clear error, not a 500

Create tests/Feature/Isolation/CrossPortalAccessTest.php — this is the most important file,
be exhaustive:
- For each of the 4 logged-in user types, assert they get a 200 on their own dashboard route
  and a redirect-to-login or 403 (be specific about which, per Phase 5's design) on ALL THREE
  other portals' dashboard routes — 4x3 = 12 assertions minimum
- branch@test.com gets 403 attempting to visit a second seeded branch's dashboard URL
- An unauthenticated client gets redirected to /login from all 4 dashboard routes
- platform@test.com's super-admin role passes a Gate::allows() check for an arbitrary/undefined
  permission string with NO explicit team id set (proving the PlatformTeamResolver default-to-0
  bypass works) while a non-super-admin platform role (seed one via the
  RolesAndPermissionsSeeder if not already present, e.g. RoleEnum::STAFF) does NOT pass the same
  check
- Same super-admin-bypass assertion for branch@test.com scoped correctly to their team/branch
- A unique-constraint regression test: attempting to create a second platform-scope role with
  the same name (relying on the resolver's default team id of 0, not an explicit null) throws a
  database unique-constraint violation — this locks in the Phase 1 rework's fix for the
  NULL-uniqueness gap

Create tests/Feature/Auth/LogoutTest.php covering:
- Logging out only ends the session on the currently active guard — log in as platform in one
  test client session is out of scope for a single HTTP test, but at minimum assert that after
  logout, the same guard's dashboard route redirects to login again

Run the full suite (`php artisan test` or `./vendor/bin/pest`) and paste me the full output.
Every test must pass — do not mark any as skipped or incomplete. If a test fails, fix the
underlying code (middleware, controller, seeder) rather than loosening the test's assertions.
```

---

## Phase 8 — Role/Permission/Branch-staff management UI

**Goal:** platform admins can manage platform roles, permissions, and the branch directory through
real CRUD screens instead of tinker/seeders; branch admins can manage their own branch's staff
within the permissions they've been granted. This is new ground for this project — the original
7-phase plan only ever seeded roles/permissions, it never gave anyone a UI to manage them.

This phase mirrors a working pattern from the `blake_lms_tenant` reference project's
`platform/roles`, `platform/permissions`, and `platform/people` pages and their backend
controllers, adapted down to this project's simpler platform/branch (no school layer) model.

### Master prompt for Cursor

```
Building on Phases 1–7 (auth, isolation, and the frontend permission infrastructure from Phase 6
are all in place and tested).

1. Backend — Platform\RoleController (app/Http/Controllers/Platform/RoleController.php):
   - index: paginated list of platform-guard roles (team id via PlatformTeamResolver's default,
     0), with search by name and a permission-count column
   - create/store: create a new platform-guard role (name unique per team+guard), assign
     permissions from PermissionEnum::forGuard('platform')
   - show: role detail with its permission list and assigned users
   - edit/update: rename (blocked for 'super-admin') and re-sync permissions
   - destroy: delete (blocked for 'super-admin' and for any role with assigned users — return a
     clear validation error, don't silently orphan assignments)
   Protect all actions with Spatie `permission:` route middleware using PermissionEnum::MANAGE_ROLES.

2. Backend — Platform\PermissionController (app/Http/Controllers/Platform/PermissionController.php):
   - index only (read-only catalog) — permissions are enum-defined and seeded, not created via UI.
     List all platform-guard permissions grouped by PermissionEnum::group(), with a role-count
     per permission.
   Protect with PermissionEnum::MANAGE_ROLES or a dedicated view-permissions permission if you
   prefer finer granularity — use your judgment, document the choice in the PR/commit.

3. Backend — Platform\BranchStaffController (app/Http/Controllers/Platform/BranchStaffController.php)
   for platform admins, and Branch\StaffController (app/Http/Controllers/Branch/StaffController.php)
   for branch admins managing their OWN branch only:
   - Platform\BranchStaffController: index/show across ALL branches (platform admin oversight),
     filterable by branch
   - Branch\StaffController: index/create/store/edit/update/destroy scoped to
     app('currentBranch') only (from Phase 5's ResolveBranchContext) — a branch admin can never
     see or modify another branch's staff, enforced at the query level, not just the UI
   - Both use the branch guard's roles (RoleEnum) when assigning staff roles, scoped to the
     correct branch team id
   - Deleting/deactivating the last super-admin of a branch should be blocked with a clear error

4. Frontend — resources/js/Pages/Platform/Roles/{Index,Create,Edit,Show}.tsx and
   resources/js/Pages/Platform/Permissions/Index.tsx and
   resources/js/Pages/Platform/BranchStaff/{Index,Show}.tsx and
   resources/js/Pages/Branch/Staff/{Index,Create,Edit}.tsx:
   - Reuse the PlatformLayout/BranchLayout and sidebar nav from Phase 6; add new nav items gated
     by PermissionEnum::MANAGE_ROLES / MANAGE_USERS / MANAGE_BRANCHES / MANAGE_BRANCH_STAFF as
     appropriate per portal
   - Role create/edit form: a shared PermissionSelector component (checkbox list grouped by
     PermissionEnum::group()) — build this as a small reusable component since both role forms
     need it
   - Use the starter kit's existing table/pagination/form components (check the shadcn
     registry for this project before hand-rolling a data table)

5. Form Requests: StoreRoleRequest/UpdateRoleRequest (name uniqueness per guard+team, reserved
   'super-admin' name blocked from creation/rename, permissions must belong to the same guard as
   the role), StoreBranchStaffRequest/UpdateBranchStaffRequest (email uniqueness, branch_id
   forced to the current branch context server-side — never trust a client-submitted branch_id).

Validation:
- Platform admin can create a new platform role, assign a subset of permissions, and a test user
  assigned that role sees exactly the nav items/permissions granted (spot check with can())
- Platform admin cannot rename or delete 'super-admin'
- Branch admin at Downtown cannot see or edit Uptown's staff via any route (try the API route
  directly with Downtown's session, not just checking the UI hides the link)
- Deleting the last super-admin of a branch is blocked with a clear validation error
- All new routes are covered by the existing `ensure.guard`/`resolve.branch` middleware from
  Phase 5 — no new bypass of the isolation boundary was introduced
- Write feature tests for the above (extend tests/Feature/ from Phase 7 rather than starting a
  separate untested surface) and paste the full test run output
```

---

## Phase 9 — Shared infrastructure (deferred)

**Goal:** port genuinely reusable, non-auth-specific UI/dev infrastructure from the `blake_lms_tenant`
reference project, once the full auth system (Phases 1–8) is complete and tested. This phase is
intentionally lower priority — do not start it until Phase 8's validation checklist is fully green.

**Why deferred:** none of the items below affect the auth/isolation boundary. Building them early
risks scope creep and distracts from getting the security-critical phases (3–5, 7–8) airtight
first.

**Scope for this phase (fleshed out in detail when its turn comes):**

- **Error boundary** — a React error boundary component with a dev-mode overlay (stack trace,
  component stack, error history in sessionStorage) and a friendly production fallback
  (reload/back/home actions), wrapping the app root similar to `blake_lms_tenant`'s
  `resources/js/components/error-boundary/` module.
- **Icon picker system** — a Lucide icon picker (`resources/js/components/icons/`,
  `resources/js/lib/icon-catalog.ts`, `icons.tsx`, `lucide-icon-cache.ts`) for any future admin UI
  that needs to let users pick an icon (e.g. branch/category icons) — generated catalog + lazy
  dynamic-import caching, not bundling all ~1,700 icons up front.
- **File upload component** — a reusable drag-drop file upload component with per-file progress,
  existing-file previews, size/type/count validation, and an avatar variant, for future features
  that need attachments (e.g. branch photos, product images, delivery proof-of-photo for drivers).
- **Laravel Telescope** — install for local/staging debugging (query log, request log, exceptions),
  gated behind a `viewTelescope` gate allow-list so it's not exposed in production without explicit
  opt-in, mirroring `blake_lms_tenant`'s `TelescopeServiceProvider` customization.

A detailed master prompt with file lists and validation checklist for this phase will be written
when Phase 8 is done and it's time to actually start it — write it fresh against the codebase's
state at that time rather than guessing now.

---

## After this: what's deliberately out of scope for now

Once Phase 9 is green, you have a working, tested, isolated 4-portal auth system with real admin
management UI and shared infra. Explicitly *not* covered yet (future work, don't let an agent
scope-creep into these):

- Branch product/stock/order CRUD
- Order placement, geo-nearest-branch calculation, delivery charge calculation
- Driver notification-on-order and accept/claim flow
- Real-time location tracking (would need broadcasting — Reverb/Pusher — not installed yet)

Worth doing a short review pass after Phase 9 before moving into any of those, just to make sure nothing in the isolation layer needs hardening once real business data is on the line.
