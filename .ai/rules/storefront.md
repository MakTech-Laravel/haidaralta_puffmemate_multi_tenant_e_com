---
paths:
  - 'resources/js/{layouts,components,pages}/storefront/**'
  - 'resources/js/lib/storefront*.ts'
  - 'app/Support/Storefront/**'
  - app/Http/Middleware/HandleInertiaRequests.php
  - 'app/Support/Storefront/StorefrontCatalog.php,config/catalog.php'
  - app/Support/Storefront/StorefrontShopQuery.php
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

Homepage featured category cards use `StorefrontCatalog::featuredCategories()`. Homepage New Arrivals / Best Selling use `StorefrontCatalog::productsWithTag` + `config('catalog.home_products_max')`. Shop uses `StorefrontShopQuery` (Meilisearch search when available, Eloquent fallback) with query-string filters: `search`, `sort`, `min_price`, `max_price`, `categories[]`, `tags[]`. Facets come from active root categories, all `ProductTag` cases, and active `PriceRange` rows — never hardcoded shop sidebar lists. Shop has **no** branch select. Product detail and Packs & Saves use `StorefrontCatalog`. Durable TS types live in `resources/js/types/storefront.ts`; features bar copy in `resources/js/lib/storefront-features.ts`. Cart / checkout / customer / driver may still use DemoCatalog until those surfaces are migrated. Header tabs use `buildStorefrontNavItems(storefront.navCategories)`. Branch session (`storefront.branch_slug` from `?branch=`) stays on StorefrontCatalog for category/PDP backends only.

## ProductCard contract

Shared `ProductCard` never shows or receives stock quantity (PDP may still show stock). Cards use fixed `aspect-[3/2]` with a real `<img>` (`object-cover`, lazy, async decode, sizes) and `/images/storefront/logo.png` fallback. Offer badge is driven by truthy `compareAtPrice`. Mapper (`mapProductCard`) prefers `Product::packSummary()` for the subtitle and uses catalog prices via `BranchProductPriceDisplay::forCatalogProduct` when no branch is selected.

## Product detail has no branch picker

Storefront PDP (`ProductSummary`) does not show Selected branch / Change branch. Do not pass `branches` or `branchId` Inertia props for product show. Catalog price still comes from `StorefrontCatalog::productDetail` (catalog or session branch when present for backend only).

## Similar products are ranked recommendations

PDP Similar Products uses `StorefrontCatalog::similarProducts` capped by `config('catalog.similar_products_max')` (default 4). Rank: same category (+100), same brand (+40), tags best_selling (+30) / popular (+20) / on_offer (+15) / new_arrival (+10), catalog compare-at (+5). Backfill from site-wide best_selling/popular when the category pool is thin. Do not sort Similar Products alphabetically only.

## Shop price ranges are platform-managed

`PriceRange` rows (label, slug, min_amount, max_amount, sort_order, status) drive shop sidebar presets. Platform CRUD at `/platform/price-ranges` under `manage-catalog`. Seed via `PriceRangeSeeder` (includes `$10,000 & above`). Slider floor/ceiling are singleton `shop_price_filter_settings` (editable on the price ranges index), not derived from preset max amounts.

## Product Scout indexes catalog prices

`Product::toSearchableArray` includes `selling_price` and `effective_price` (catalog effective). Meilisearch filterable/sortable lists in `config/scout.php` must stay in sync. After changing index settings, reimport products.

## Similar products ranked recommendations
PDP Similar Products ranks by category (+100), brand (+40), tags best_selling/popular/on_offer/new_arrival, then compare-at. Cap via catalog.similar_products_max (default 4). Backfill from site-wide best_selling/popular when the category pool is thin.

## Shop listing uses Meilisearch filters
Shop and category listings filter in Meilisearch (status, category_ids, tags, effective_price) when SCOUT_DRIVER=meilisearch, then hydrate those ids. Collection/test driver uses the SQL effective-price CASE. Price bounds are SQL literals, not float bindings (SQLite drops the row). filterOptions is an Inertia once prop on the shop page only. Reimport products after searchableAttributes changes.
