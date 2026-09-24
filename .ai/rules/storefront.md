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

`CategorySeeder` seeds root featured categories matching homepage cards: cigarettes, vapes, tobacco, nicotine-pouches, accessories (max `featured_categories_max` = 5). Vapes children disposables / pod-systems get category images and stay out of header nav. Packs & Saves and Wholesale remain fixed pages — never catalog categories.

## Nav categories are cached + Inertia once

`StorefrontCatalog::navCategories()` is `Cache::rememberForever` (survives `migrate:fresh`). Category model `saved`/`deleted`/`restored`/`forceDeleted`, CategoryService mutations, and `CategorySeeder` all call `forgetNavCategories()` (bumps version). Share via `Inertia::once(...)->as('storefront-nav-categories-{version}')` so SPA navigations skip re-query; version change forces clients to refetch. Header category tabs come only from that prop — never hard-coded category labels.

## Catalog sources by surface

Homepage featured category cards use `StorefrontCatalog::featuredCategories()`. Homepage New Arrivals / Best Selling use `StorefrontCatalog::productsWithTag` + `config('catalog.home_products_max')`. Shop, category, product detail, and Packs & Saves use `StorefrontCatalog` (DB) — never `DemoCatalog`. Durable TS types live in `resources/js/types/storefront.ts`; features bar copy in `resources/js/lib/storefront-features.ts`. Do not import product data from `storefront-demo.ts` (deprecated re-exports only). Cart / checkout / customer / driver may still use DemoCatalog until those surfaces are migrated. Header tabs use `buildStorefrontNavItems(storefront.navCategories)`. Branch session (`storefront.branch_slug` from `?branch=`) stays on StorefrontCatalog.

## ProductCard contract

Shared `ProductCard` never shows or receives stock quantity (PDP may still show stock). Cards use fixed `aspect-[3/2]` with a real `<img>` (`object-cover`, lazy, async decode, sizes) and `/images/storefront/logo.png` fallback. Offer badge is driven by truthy `compareAtPrice`. Mapper (`mapProductCard`) prefers `Product::packSummary()` for the subtitle and uses catalog prices via `BranchProductPriceDisplay::forCatalogProduct` when no branch is selected.

## Product detail has no branch picker

Storefront PDP (`ProductSummary`) does not show Selected branch / Change branch. Do not pass `branches` or `branchId` Inertia props for product show. Catalog price still comes from `StorefrontCatalog::productDetail` (catalog or session branch when present for backend only).
