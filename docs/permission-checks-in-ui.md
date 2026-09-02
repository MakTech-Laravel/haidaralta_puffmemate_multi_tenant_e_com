# Permission checks in the UI

`usePermission()` (`resources/js/hooks/use-permissions.tsx`) is the single frontend entry point
for authorization decisions. It reads the flat `roles`/`permissions`/`is_super_admin` arrays
shared via Inertia (see [layouts-and-dashboards.md](layouts-and-dashboards.md)) — it never makes
its own HTTP request.

```ts
const {
  can,       // (permission: PermissionKey) => boolean
  canAny,    // (permissions: PermissionKey[]) => boolean — true if user has at least one
  canAll,    // (permissions: PermissionKey[]) => boolean — true only if user has every one
  hasRole,   // (role: string) => boolean
  hasAnyRole // (roles: string[]) => boolean
} = usePermission();
```

**Super-admin bypass:** every `can*` method returns `true` immediately if
`auth.user.is_super_admin` is set, mirroring the backend's `Gate::before` bypass — the frontend
never has to special-case super-admin separately in each component.

`PermissionKey` (in `resources/js/types/permissions.ts`) is a string-literal union mirroring
`PermissionEnum`'s cases, so passing a typo'd permission string is a compile-time TypeScript
error, not a silent runtime `false`.

## Gating a button or section

```tsx
function RolesPageActions() {
  const { can } = usePermission();

  return (
    <div className="flex gap-2">
      {can('manage-roles') && (
        <Button onClick={openCreateRoleModal}>New role</Button>
      )}
    </div>
  );
}
```

## Gating with multiple acceptable permissions

```tsx
function StaffDirectoryLink() {
  const { canAny } = usePermission();

  if (!canAny(['manage-branch-staff', 'manage-branch-orders'])) {
    return null;
  }

  return <Link href="/branch/staff">Staff directory</Link>;
}
```

## Requiring every permission in a set

```tsx
function BulkArchiveButton() {
  const { canAll } = usePermission();

  // Only show if the user can both view AND manage — a narrower gate than canAny
  if (!canAll(['manage-branch-products', 'manage-branch-orders'])) return null;

  return <Button onClick={bulkArchive}>Archive selected</Button>;
}
```

## Role-based gating (rare — prefer permissions over roles)

Use `hasRole`/`hasAnyRole` only for role-specific UI copy (e.g. "You are viewing this as an
Admin"), not as a substitute for permission checks — roles can be renamed or restructured, but
permission strings are the stable contract. Business-logic gating should almost always use
`can`/`canAny`/`canAll`.

```tsx
function WelcomeBanner() {
  const { hasRole } = usePermission();

  return (
    <p>
      Welcome back{hasRole('super-admin') ? ', Super Admin' : ''}!
    </p>
  );
}
```

## Navigation gating

The `NavNode.permissions` field (an ANY-match array) is the primary place permission checks
happen for navigation — see [layouts-and-dashboards.md](layouts-and-dashboards.md)'s
`filterNavNodes()`. Individual page components generally only need `usePermission()` for
in-page actions (buttons, sections), not for hiding entire nav links — that's the nav component's
job.

## Defense in depth: UI hiding is not authorization

Every permission check in the UI must have a matching backend check (route middleware,
`->can()`, or a Form Request authorization rule). Hiding a button with `can()` only improves UX —
it does not stop a request crafted directly against the route. See
[middleware-and-tenancy.md](middleware-and-tenancy.md) and
[permissions.md](permissions.md#adding-a-new-permission-step-by-step) for where the real
enforcement lives.

## Testing components that use `usePermission()`

When writing frontend tests for a component that calls `usePermission()`, wrap it in an Inertia
page context with the `auth.user.permissions`/`roles`/`is_super_admin` props set to whatever the
test scenario needs — don't mock the hook itself, since that would stop the test from catching a
real wiring break between the shared props and the hook.
