# Layouts and dashboards

Each of the 4 portals has its own Inertia layout and sidebar navigation, but they all follow the
**same composition pattern** — one layout resolver "shape" reused 4 times rather than 4 unrelated
implementations. This mirrors the approach in a reference multi-tenant project
(`blake_lms_tenant`), which composes its portal layouts identically.

## Shared props that drive layout decisions

`app/Http/Middleware/HandleInertiaRequests.php` shares, on every authenticated request:

```php
[
    'auth' => [
        'user' => [
            // ...user attributes...
            'roles' => $user->getRoleNames(),          // for the currently active guard/team
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'is_super_admin' => $user->hasRole(RoleEnum::SUPER_ADMIN->value),
        ],
    ],
    'portal' => $portal,   // 'platform' | 'branch' | 'driver' | 'customer', derived from user_type
    'branch' => $branch,   // null outside the branch portal; { id, name, slug } inside it
]
```

`portal` lets the frontend know which layout/nav to use without re-deriving it from the URL, and
`roles`/`permissions` are always scoped to whichever Spatie team is active for the request (`0` by
default via `PlatformTeamResolver`, or the resolved branch's id after `ResolveBranchContext`).

## Layout files

```
resources/js/Layouts/
├── PlatformLayout.tsx
├── BranchLayout.tsx
├── DriverLayout.tsx
└── CustomerLayout.tsx
```

Each layout is a thin shell composing:

- a **sidebar** (the data-driven nav component, see below)
- a **top bar** showing the portal name, the logged-in user's name/email, and a logout button
  (`router.post('/logout')`)
- a **content slot** for the page

```tsx
// resources/js/Layouts/BranchLayout.tsx (shape shared by all 4 layouts)
export default function BranchLayout({ children }: PropsWithChildren) {
    const { branch } = usePage<PageProps>().props;

    return (
        <div className="flex min-h-screen">
            <SidebarNav nodes={branchNav} />
            <div className="flex-1">
                <TopBar title={branch?.name ?? 'Branch'} />
                <main className="p-6">{children}</main>
            </div>
        </div>
    );
}
```

Pages opt into a layout the same way the starter kit already does (either a static `Page.layout =
BranchLayout` assignment or wrapping in the page component — match whatever convention this
project's starter kit uses elsewhere).

## Data-driven navigation

```
resources/js/components/navigation/
├── types.ts        # NavNode interface
├── nav-utils.ts     # filterNavNodes()
└── sidebar-nav.tsx  # renders the filtered tree
```

```ts
// types.ts
interface NavNode {
    title: string;
    href: string;
    icon?: LucideIcon;
    permissions?: PermissionKey[]; // ANY-match: user needs at least one to see this node
    items?: NavNode[];
}
```

```ts
// nav-utils.ts
export function filterNavNodes(
    nodes: NavNode[],
    canAny: (perms: PermissionKey[]) => boolean,
): NavNode[] {
    return nodes
        .map((node) => {
            const items = node.items
                ? filterNavNodes(node.items, canAny)
                : undefined;
            const visible = !node.permissions || canAny(node.permissions);
            if (!visible && (!items || items.length === 0)) return null;
            return { ...node, items };
        })
        .filter((n): n is NavNode => n !== null);
}
```

`sidebar-nav.tsx` calls `usePermission().canAny()` internally and renders the filtered tree.
Nodes without a `permissions` key (driver/customer nav items — those portals have no roles at
all) are always visible; nodes with a `permissions` array are hidden unless the user has at least
one of the listed permissions. A parent group with no visible children after filtering is dropped
entirely rather than rendered empty.

Each portal keeps its own small nav array, e.g.:

```ts
// resources/js/components/platform/nav.ts
export const platformNav: NavNode[] = [
    {
        title: 'Dashboard',
        href: '/platform/dashboard',
        permissions: ['view-dashboard'],
    },
    { title: 'Roles', href: '/platform/roles', permissions: ['manage-roles'] }, // Phase 8
    {
        title: 'Branches',
        href: '/platform/branches',
        permissions: ['manage-branches'],
    }, // Phase 8
];
```

## Dashboard controllers

```
app/Http/Controllers/Platform/DashboardController.php → Pages/Platform/Dashboard.tsx
app/Http/Controllers/Branch/DashboardController.php   → Pages/Branch/Dashboard.tsx   (+ currentBranch)
app/Http/Controllers/Driver/DashboardController.php   → Pages/Driver/Dashboard.tsx
app/Http/Controllers/Customer/DashboardController.php → Pages/Customer/Dashboard.tsx
```

The branch dashboard controller passes `app('currentBranch')` as a prop so the page (and
`useBranch()`) can display which branch context is active — useful for manually spot-checking
isolation while developing.

## How to add a new dashboard/admin page

1. Add a controller action under the correct portal namespace (`app/Http/Controllers/<Portal>/`).
2. Add the route inside that portal's existing `Route::middleware([...])->prefix(...)->group()`
   in `routes/web.php` (see [middleware-and-tenancy.md](middleware-and-tenancy.md)) — gate it with
   `permission:<name>` middleware if it's not open to every user of that portal.
3. Add the Inertia page under `resources/js/Pages/<Portal>/...tsx`, using that portal's layout.
4. Add a `NavNode` entry to that portal's nav array, with a `permissions` array matching the
   route's gate (omit `permissions` only for driver/customer, which have no roles).
5. If the page needs branch context, read it via `useBranch()` rather than re-deriving it from
   props manually.
6. Add a feature test confirming the route is reachable by the intended portal/permission and
   rejected for everyone else (extend the pattern in
   `tests/Feature/Isolation/CrossPortalAccessTest.php`).
