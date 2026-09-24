---
paths:
    - 'resources/js/{layouts,components,pages}/storefront/**'
    - 'resources/js/lib/storefront*.ts'
    - app/Support/Storefront/**
    - app/Http/Middleware/HandleInertiaRequests.php
---

# Storefront

These rules apply only to storefront paths above. Do not apply this folder layout to platform, branch, admin, or other app areas.

## Storefront UI is disposable

Storefront UI under resources/js/{layouts,components,pages}/storefront and lib/storefront-demo.ts is temporary placeholder until Figma. Prefer deleting/replacing that tree; keep public route names home/shop/about/contact when swapping UI.

## Header nav: fixed slots + cached categories

Header = Home · Shop · [DB categories with `show_in_nav`, ordered by `nav_sort`] · Packs & Saves · Wholesale. Packs & Saves (`/packs-saves`) and Wholesale (`/wholesale`) are fixed pages — never catalog categories. Logo uses Application Settings (`useAppBrand` → `application.logoUrl`) with `/images/storefront/logo.png` fallback. Profile icon uses shared `auth.dashboardUrl` (PortalAuth path by user type), not hard-coded `/dashboard`.

## Catalog seeder featured set

`CatalogSeeder` seeds root featured categories matching homepage cards: cigarettes, vapes, tobacco, nicotine-pouches, accessories (max `featured_categories_max` = 5). Vapes children disposables / pod-systems get category images and stay out of header nav. Packs & Saves and Wholesale remain fixed pages — never catalog categories.

## Nav categories are cached + Inertia once

`StorefrontCatalog::navCategories()` is `Cache::rememberForever` (survives `migrate:fresh`). Category model `saved`/`deleted`/`restored`/`forceDeleted`, CategoryService mutations, and `CatalogSeeder` all call `forgetNavCategories()` (bumps version). Share via `Inertia::once(...)->as('storefront-nav-categories-{version}')` so SPA navigations skip re-query; version change forces clients to refetch. Header category tabs come only from that prop — never hard-coded category labels.

## Catalog sources by surface

Homepage featured category cards use `StorefrontCatalog::featuredCategories()` (DB `featured` + slogan/image) — not DemoCatalog. Shop / product detail / carousels may still use DemoCatalog until those surfaces are migrated. Header tabs use `buildStorefrontNavItems(storefront.navCategories)`. Branch session (`storefront.branch_slug` from `?branch=`) stays on StorefrontCatalog.
