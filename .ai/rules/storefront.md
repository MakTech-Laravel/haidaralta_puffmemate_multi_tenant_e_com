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

## Catalog seeder is cig / tobacco / vapes only

`CatalogSeeder` seeds root categories cigarettes, tobacco, vapes (plus optional vapes children disposables / pod-systems). Do not seed Packs & Saves, Wholesale, pouches, accessories, or e-liquids as categories.

## Nav categories are cached + Inertia once

`StorefrontCatalog::navCategories()` is `Cache::rememberForever`; CategoryService create/update/delete/restore calls `forgetNavCategories()` (bumps version). Share via `Inertia::once(...)->as('storefront-nav-categories-{version}')` so SPA navigations skip re-query; version change forces clients to refetch.

## Catalog sources by surface

Home, shop, category, and product detail use `DemoCatalog` so card ids (`"1"`, `"2"`, …) resolve and prices show as plain `$…` (no “Start from”, no branch required). Header tabs use `buildStorefrontNavItems(storefront.navCategories)` — not static demo chrome. Branch session (`storefront.branch_slug` from `?branch=`) stays on StorefrontCatalog. Cart/checkout may still use DemoCatalog until checkout is built.
