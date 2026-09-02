# Permissions

This project uses [`spatie/laravel-permission`](https://spatie.be/docs/laravel-permission) with
**Teams** enabled (`config/permission.php` → `'teams' => true`, `'team_foreign_key' => 'branch_id'`).
A "team" in Spatie's terms is a **branch** here — there is no deeper nesting (no school/organization
layer as in some reference multi-tenant projects; this project's only tenancy boundary is
platform vs branch).

## The team-scoping model: reserved id `0`, not `null`

Platform-scope roles need to exist **outside** any branch's team, but Spatie's teams feature
requires every role row to carry a team id. Two options exist:

1. Use `branch_id = null` for platform roles.
2. Reserve a concrete sentinel value (`0`) for the platform team.

**This project uses option 2.** Here's why option 1 is a trap: PostgreSQL does not treat
`NULL = NULL` as equal inside a unique constraint. A unique index on
`(branch_id, name, guard_name)` with a nullable `branch_id` will **not** stop two rows like
`('super-admin', NULL, 'platform')` from both being inserted — a silent duplicate-role bug with
no database-level guard against it. Reserving `0` keeps the value concrete, so the unique
constraint (and Spatie's original composite-primary-key pivot schema) works exactly as designed,
with no patched migrations.

### `PlatformTeamResolver`

```php
// app/Support/PlatformTeamResolver.php
class PlatformTeamResolver extends \Spatie\Permission\DefaultTeamResolver
{
    public const PLATFORM_TEAM_ID = 0;

    public function getPermissionsTeamId(): int|string|null
    {
        return parent::getPermissionsTeamId() ?? self::PLATFORM_TEAM_ID;
    }
}
```

Registered in `config/permission.php`:

```php
'team_resolver' => \App\Support\PlatformTeamResolver::class,
```

**Effect:** on any request where nobody has explicitly called `setPermissionsTeamId()` (i.e. every
platform, driver, and customer route), permission/role lookups automatically resolve against team
id `0`. On branch routes, `ResolveBranchContext` middleware calls
`setPermissionsTeamId($branch->id)` explicitly, overriding the default for that request.

**Important distinction:** `branch_id = 0` is a Spatie **team-scoping sentinel only**. It is never
written to `users.branch_id`. A platform user's `users.branch_id` column stays `null` — that
column answers "which branch record does this user belong to" (platform users belong to none),
which is a different question from "which Spatie team should permission checks resolve against
for this request" (answered by the resolver).

`branches.id` is a PostgreSQL `bigserial` starting at `1`, so `0` never collides with a real
branch.

## Enums

Roles and permissions are backed by PHP enums instead of raw strings, so typos are caught by
static analysis instead of at runtime.

### `UserType`

```php
enum UserType: string
{
    case PLATFORM = 'platform';
    case BRANCH = 'branch';
    case DRIVER = 'driver';
    case CUSTOMER = 'customer';

    public function guard(): string
    {
        return $this->value; // guard names match user types 1:1 in this project
    }
}
```

### `RoleEnum`

```php
enum RoleEnum: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case STAFF = 'staff';
}
```

The same four role **names** exist on both the `platform` and `branch` guards — they are
different rows (different `guard_name`, different `branch_id` team scope), not shared records.

### `PermissionEnum`

```php
enum PermissionEnum: string
{
    // platform guard
    case VIEW_DASHBOARD = 'view-dashboard';
    case MANAGE_USERS = 'manage-users';
    case MANAGE_ROLES = 'manage-roles';
    case MANAGE_BRANCHES = 'manage-branches';

    // branch guard
    case VIEW_BRANCH_DASHBOARD = 'view-branch-dashboard';
    case MANAGE_BRANCH_PRODUCTS = 'manage-branch-products';
    case MANAGE_BRANCH_ORDERS = 'manage-branch-orders';
    case MANAGE_BRANCH_STAFF = 'manage-branch-staff';

    public function guard(): string { /* 'platform' or 'branch' based on the case */ }
    public function group(): string { /* short grouping label for admin UI */ }

    public static function forGuard(string $guard): array
    {
        return array_filter(self::cases(), fn (self $p) => $p->guard() === $guard);
    }
}
```

Platform and branch permission namespaces are separated by **guard name**, not by an extra
`domain` column — this project doesn't need a `PermissionDomain` enum since guards already do
that job.

## Adding a new permission (step-by-step)

1. Add a case to `app/Enums/PermissionEnum.php` with the new permission string, and make sure
   `guard()` and `group()` handle it correctly.
2. Re-run `database/seeders/RolesAndPermissionsSeeder.php` (or `php artisan migrate:fresh --seed`
   in development) so the permission row is created via `Permission::firstOrCreate`.
3. Assign it to whichever roles should have it in the seeder's role-tier logic (`admin`,
   `manager`, `staff` — never `super-admin`, which bypasses via `Gate::before` instead).
4. If the permission gates a route, add `permission:<name>` middleware to that route (or a
   `->can()` check in the controller).
5. If it gates UI, add it to the relevant `NavNode.permissions` array or call
   `usePermission().can('<name>')` in the component — see
   [permission-checks-in-ui.md](permission-checks-in-ui.md).
6. Add/extend a feature test asserting the permission is enforced (not just that the UI hides a
   button — assert the route itself rejects an unauthorized request).

## Super-admin bypass

```php
// bootstrap/app.php (or AuthServiceProvider, per Laravel 13 convention)
Gate::before(function ($user, string $ability) {
    return $user->hasRole(RoleEnum::SUPER_ADMIN->value) ? true : null;
});
```

`hasRole()` is team-aware: it checks against whichever team id is currently active for the
request (`0` by default on platform routes via `PlatformTeamResolver`, or the branch's id after
`ResolveBranchContext` has run on branch routes). A platform super-admin therefore only bypasses
platform-scope checks; a branch super-admin only bypasses checks within their own branch's team —
there is no cross-portal bypass leak.

Super-admin roles are seeded with **zero permissions assigned**. All of their access comes from
the `Gate::before` bypass, never from an explicit permission grant. This means removing a
permission from the seeder never accidentally locks out a super-admin, and it means the
permission matrix for `admin`/`manager`/`staff` is the real, testable authorization surface.
