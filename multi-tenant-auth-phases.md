# Multi-Tenant Auth System — Phase-by-Phase Implementation Plan

**Project:** haidaralta-multi-tenant-e-com
**Stack:** Laravel 13, Inertia 3, React starter kit, Spatie laravel-permission (teams on), PostgreSQL

## Locked-in decisions (don't relitigate these mid-build)

- Single `users` table, `user_type` column (`platform` | `branch` | `driver` | `customer`)
- 4 separate auth guards (`platform`, `branch`, `driver`, `customer`), all on the same `users` provider
- One shared `/login` page (React/Inertia); backend looks up the account by email, reads its `user_type`, authenticates on the matching guard, redirects to that portal's dashboard
- Spatie `teams` enabled, `team_foreign_key` = `branch_id`; branch users get their `branch_id` as permission-team scope; platform stays team-less (`branch_id` null)
- Super-admin bypass (both platform and branch scope) = role-name check `'super-admin'` via `Gate::before`, team-aware
- Driver and customer: no roles/permissions, just guard + ownership checks
- Scope for this build: auth, forgot/reset password, redirect-by-type, route protection, 4 basic protected dashboard shells. No business logic yet.

Run every phase in order — each one assumes the previous is done and tested. Don't let the agent skip the test step to "save time"; the whole point of this system is airtight isolation between portals, and that's exactly the kind of thing that silently breaks without tests.

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

**Goal:** database and model layer ready. No auth flow yet.

### Master prompt for Cursor

```
You are working in a fresh Laravel 13 project (React + Inertia starter kit already installed,
PostgreSQL already connected). Do the following, in order:

1. Install spatie/laravel-permission via composer and publish its config + migration.
2. In config/permission.php, set 'teams' => true and 'team_foreign_key' => 'branch_id'.
3. Create a migration to add to the existing users table:
   - user_type: string, not nullable, no default (values will be 'platform','branch','driver','customer')
   - branch_id: foreignId, nullable, constrained references branches, nullOnDelete
   - is_active: boolean, default true
   Do NOT drop or rename existing columns from the starter kit's users table.
4. Create a `branches` table migration: id, name (string), slug (string, unique), address (text, nullable),
   latitude (decimal 10,7 nullable), longitude (decimal 10,7 nullable), is_active (boolean default true), timestamps.
5. Register 4 additional guards in config/auth.php — platform, branch, driver, customer — all
   driver=session, provider=users. Keep the default 'web' guard untouched. Add matching entries
   under 'passwords' for each of the 4 guards (provider=users, table=password_reset_tokens).
6. Create app/Models/Branch.php with $fillable for the columns above, and a users() hasMany relation.
7. Update app/Models/User.php:
   - use Spatie\Permission\Traits\HasRoles
   - add branch_id and user_type and is_active to $fillable
   - add a branch() belongsTo(Branch::class) relation
   - add boolean helper methods isPlatform(), isBranch(), isDriver(), isCustomer() that check user_type
   - add a scope or accessor `guardName()` that returns the guard string matching user_type
     (this will be used everywhere else in the app to avoid hardcoding guard names)
8. Run `php artisan migrate` against the connected PostgreSQL database and confirm it succeeds
   with no errors. Show me the output.

Validation before you consider this phase done:
- `php artisan migrate:status` shows all migrations ran
- `php artisan tinker` → `App\Models\User::first()` does not error (table structure is valid even if empty)
- config/auth.php has exactly 5 session guards: web, platform, branch, driver, customer, all on the users provider
- Do not write any controllers, routes, or React pages in this phase — foundation only.
```

**You confirm before moving on:** migrations ran clean, `branches` table exists, `users` table has the 3 new columns, `config/auth.php` has all 5 guards.

---

## Phase 2 — Roles, permissions, and seed data

**Goal:** default roles/permissions exist, plus at least one test user per portal type to log in with.

### Master prompt for Cursor

```
Building on Phase 1 (spatie/laravel-permission installed, teams enabled with team_foreign_key
'branch_id', users table has user_type/branch_id/is_active).

1. Create database/seeders/RolesAndPermissionsSeeder.php that:
   - Defines a starter permission set as string constants or an array, covering at minimum:
     'view-dashboard', 'manage-users', 'manage-roles', 'manage-branches' (platform scope)
     'view-branch-dashboard', 'manage-branch-products', 'manage-branch-orders', 'manage-branch-staff' (branch scope)
   - Creates all permissions via Spatie's Permission::firstOrCreate (guard_name aware — platform
     permissions use guard 'platform', branch permissions use guard 'branch').
   - Creates roles 'super-admin', 'admin', 'manager', 'staff' on guard 'platform' (team-less,
     i.e. do NOT set a team id for these — they must be created while
     app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(null)).
   - Creates roles 'super-admin', 'admin', 'manager', 'staff' on guard 'branch' PER SEEDED BRANCH
     (teams-scoped — set the team id to that branch's id before creating/assigning).
   - Gives 'super-admin' roles (both guards) no permissions at all — they bypass via Gate::before,
     defined in Phase 5, not via permission assignment.
   - Gives 'admin' most permissions, 'manager' a mid-tier subset, 'staff' minimal — use your judgment,
     this is just seed data to develop against, not final business rules.

2. Create database/seeders/BranchSeeder.php that creates 2 sample branches (e.g. "Downtown",
   "Uptown") with realistic slugs.

3. Create database/seeders/UserSeeder.php that creates exactly one test user per type, all
   with password 'password' (hashed), all is_active = true:
   - platform@test.com — user_type=platform, branch_id=null, assign role 'super-admin' on guard 'platform'
   - branch@test.com — user_type=branch, branch_id = first seeded branch's id, assign role
     'super-admin' on guard 'branch' scoped to that branch's team id
   - driver@test.com — user_type=driver, branch_id=null, no role needed
   - customer@test.com — user_type=customer, branch_id=null, no role needed

4. Wire all three seeders into DatabaseSeeder::run() in the correct order (permissions/roles →
   branches → users, since users depends on branches existing).

5. Run `php artisan migrate:fresh --seed` and show me the output. Then confirm via tinker that
   `App\Models\User::where('email','platform@test.com')->first()->hasRole('super-admin')` returns true
   (remember to reset the permissions team id to null in tinker before this check, e.g.
   app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(null)).

Validation:
- migrate:fresh --seed runs with zero errors
- 4 users exist, one per user_type
- 2 branches exist
- platform@test.com has role super-admin on guard platform with team id null
- branch@test.com has role super-admin on guard branch scoped to their branch's team id
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
       customer → /customer/dashboard
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
- Logging in as customer@test.com lands on /customer/dashboard
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
   - If Auth::user()->hasRole('super-admin') is true for the CURRENTLY ACTIVE guard (both platform
     and branch use the role name 'super-admin', so check hasRole with the appropriate guard_name
     matching Auth::user()->guardName()), return true unconditionally (full bypass, gate-pass style).
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
```

---

## Phase 6 — Basic protected dashboard UI (4 portals)

**Goal:** something to actually look at and click "logout" on.

### Master prompt for Cursor

```
Building on Phase 5 (routes and middleware exist and are verified working).

1. Create one Inertia layout per portal, matching the starter kit's existing layout conventions:
   resources/js/Layouts/PlatformLayout.tsx
   resources/js/Layouts/BranchLayout.tsx
   resources/js/Layouts/DriverLayout.tsx
   resources/js/Layouts/CustomerLayout.tsx
   Each should render: a simple top bar with the portal name, the logged-in user's name/email,
   and a logout button (POST to /logout, use Inertia's router.post). Keep styling minimal/basic —
   reuse whatever CSS/UI kit the starter kit already ships with (Tailwind, shadcn, etc.) rather
   than introducing a new one.

2. Create matching controllers:
   app/Http/Controllers/Platform/DashboardController.php → renders resources/js/Pages/Platform/Dashboard.tsx
   app/Http/Controllers/Branch/DashboardController.php → renders resources/js/Pages/Branch/Dashboard.tsx,
     passing the current branch (from app('currentBranch')) as a prop
   app/Http/Controllers/Driver/DashboardController.php → renders resources/js/Pages/Driver/Dashboard.tsx
   app/Http/Controllers/Customer/DashboardController.php → renders resources/js/Pages/Customer/Dashboard.tsx
   Each Dashboard.tsx page wraps itself in its matching Layout and shows a simple "Welcome, {name}"
   message plus (for platform/branch) the user's current role name(s).

3. Wire these controllers into the route groups defined in Phase 5 (replace the placeholder
   GET /dashboard closures with these controller actions).

4. For the branch dashboard specifically, also display the branch name/slug so it's visually
   obvious which branch context you're in — useful for manually testing isolation later.

Validation:
- All 4 test users can log in via /login and land on a dashboard that renders without errors,
  shows their name, and (platform/branch) shows their super-admin role
- Logout button works and returns to /login
- Directly hitting another portal's dashboard URL while logged in still redirects/403s as per Phase 5
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
  permission string (proving the Gate::before bypass works) while a non-super-admin platform
  role (seed one via the RolesAndPermissionsSeeder if not already present, e.g. 'staff') does NOT
  pass the same check
- Same super-admin-bypass assertion for branch@test.com scoped correctly to their team/branch

Create tests/Feature/Auth/LogoutTest.php covering:
- Logging out only ends the session on the currently active guard — log in as platform in one
  test client session is out of scope for a single HTTP test, but at minimum assert that after
  logout, the same guard's dashboard route redirects to login again

Run the full suite (`php artisan test` or `./vendor/bin/pest`) and paste me the full output.
Every test must pass — do not mark any as skipped or incomplete. If a test fails, fix the
underlying code (middleware, controller, seeder) rather than loosening the test's assertions.
```

---

## After this: what's deliberately out of scope for now

Once Phase 7 is green, you have a working, tested, isolated 4-portal auth system. Explicitly *not* covered yet (future work, don't let an agent scope-creep into these):

- Permission-driven sidebar show/hide (needs actual nav components + `can()` checks wired into them)
- Branch product/stock/order CRUD
- Order placement, geo-nearest-branch calculation, delivery charge calculation
- Driver notification-on-order and accept/claim flow
- Real-time location tracking (would need broadcasting — Reverb/Pusher — not installed yet)

Worth doing a short review pass after Phase 7 before moving into any of those, just to make sure nothing in the isolation layer needs hardening once real business data is on the line.
