# Spatie Media Library + WebP — Change Log (A–Z)

Full inventory of what changed in the media migration. Path-based image columns → Spatie `media` table; raster uploads store as **WebP**; **SVG stays SVG**. Conversation attachments were **not** migrated.

Local after pull: `php artisan migrate:fresh --seed`

---

## Summary

| Area | What changed |
| --- | --- |
| Packages | Added `spatie/laravel-medialibrary`, `spatie/laravel-image-optimizer` |
| Storage model | Images live in `media` table + collections, not string path columns |
| Upload pipeline | `MediaUploadService` converts raster → WebP; SVG unchanged; optimizer soft-fails if binaries missing |
| Schema (pre-handover) | Path columns / gallery tables removed from original `create_*` migrations |
| Seeders | Attach committed `public/images/...` via media (preserving originals) |
| Command | `media:migrate-legacy-paths` attaches seed assets into empty collections |
| Removed | `PublicDiskMedia`, `ProductImage`, `PackImage`, `product_images` / `pack_images` tables |

---

## A. New files (added)

| File | Purpose |
| --- | --- |
| [`app/Services/Media/MediaUploadService.php`](app/Services/Media/MediaUploadService.php) | Central upload: `syncSingle`, `syncMany`, `deleteFromCollection`, `clear`, `attachFromPath`; WebP + SVG rules |
| [`app/Models/Concerns/InteractsWithAppMedia.php`](app/Models/Concerns/InteractsWithAppMedia.php) | Trait on top of Spatie `InteractsWithMedia` (single/multi collections, URL helpers) |
| [`app/Support/CatalogMediaPayload.php`](app/Support/CatalogMediaPayload.php) | Catalog gallery / edit-form media payload helpers |
| [`app/Console/Commands/Media/MigrateLegacyPathsCommand.php`](app/Console/Commands/Media/MigrateLegacyPathsCommand.php) | Artisan `media:migrate-legacy-paths` (`--dry-run`) |
| [`config/media-library.php`](config/media-library.php) | Spatie Media Library config (`disk` = `public`, conversions not queued by default) |
| [`config/image-optimizer.php`](config/image-optimizer.php) | Spatie Image Optimizer config |
| [`database/migrations/2026_10_04_045555_create_media_table.php`](database/migrations/2026_10_04_045555_create_media_table.php) | Spatie `media` table |
| [`tests/Feature/MediaUploadServiceTest.php`](tests/Feature/MediaUploadServiceTest.php) | WebP / SVG / clear collection tests |
| [`tests/Feature/MigrateLegacyPathsCommandTest.php`](tests/Feature/MigrateLegacyPathsCommandTest.php) | Legacy attach command tests |
| [`.ai/rules/concerns-support.md`](.ai/rules/concerns-support.md) | Recorded rule: raster → WebP via `MediaUploadService` |

---

## B. Deleted files

| File | Why |
| --- | --- |
| `app/Support/PublicDiskMedia.php` | Replaced by `MediaUploadService` |
| `app/Models/ProductImage.php` | Gallery → Product media collection `gallery` |
| `app/Models/PackImage.php` | Gallery → Pack media collection `gallery` |
| `database/factories/ProductImageFactory.php` | Model removed |
| `database/factories/PackImageFactory.php` | Model removed |
| `database/migrations/2026_09_13_044700_create_product_images_table.php` | Table retired (pre-handover) |

---

## C. Composer / config

| File | Change |
| --- | --- |
| `composer.json` / `composer.lock` | Require medialibrary + image-optimizer |
| `config/media-library.php` | Published; `queue_conversions_by_default` default `false` |
| `config/image-optimizer.php` | Published |

---

## D. Models → HasMedia collections

| Model | Collections | Removed columns / notes |
| --- | --- | --- |
| `User` | `avatar` | Dropped `avatar`; added `avatarUrl()` |
| `Branch` | `logo` | Dropped `logo`; added `logoUrl()` |
| `DriverProfile` | `vehicle` | Dropped `vehicle_image_path`; added `vehicleImageUrl()` |
| `Category` | `image` | Dropped `image`; `imageUrl()` → media |
| `Product` | `image`, `gallery` | Dropped `image`; no `ProductImage` relation |
| `Pack` | `image`, `gallery` | Dropped `image`; no `PackImage` / `pack_images` |
| `HomeBanner` | `image` | Dropped `image_path`; `imageUrl()` |
| `CmsPage` | `hero`, `media` | Uploads no longer written into JSON payload paths |
| `ApplicationSetting` | `logo`, `favicon`, `pwa_192`, `pwa_512`, `pwa_maskable`, `apple_touch` | Dropped six `*_path` columns |

---

## E. Migrations edited (pre-handover — original create files)

| Migration | Change |
| --- | --- |
| `0001_01_01_000000_create_users_table.php` | Removed `avatar` |
| `2026_09_01_105222_create_branches_table.php` | Removed `logo` |
| `2026_09_13_042224_create_categories_table.php` | Removed `image` |
| `2026_09_13_042225_create_products_table.php` | Removed `image` |
| `2026_09_13_044700_create_product_images_table.php` | **Deleted** |
| `2026_09_15_031000_create_driver_profiles_table.php` | Removed `vehicle_image_path` |
| `2026_09_21_091145_create_application_settings_table.php` | Removed logo/favicon/PWA/apple path columns |
| `2026_09_22_114911_create_home_banners_table.php` | Removed `image_path` |
| `2026_09_28_093116_create_packs_tables.php` | Removed `packs.image` and entire `pack_images` create |
| `2026_10_04_045555_create_media_table.php` | **New** Spatie media table |

No `Schema::table` drop/backfill migrations (pre-handover rule).

---

## F. Services updated (uploads via MediaUploadService)

| Service | Change |
| --- | --- |
| `PlatformAdminService` | Avatar → media |
| `BranchStaffService` | Avatar → media; payloads use `avatarUrl()` |
| `CustomerAccountService` | Avatar → media |
| `DriverService` | Avatar + vehicle → media |
| `BranchService` | Logo → media; `logoUrl(Branch)` |
| `CategoryService` | Category image → media |
| `ProductService` | Primary + gallery → media |
| `PackService` | Primary + gallery → media |
| `HomeBannerService` | Banner image → media; seed defaults attach media |
| `CmsPageService` | Hero/media uploads → collections; payload keeps seed static paths as fallback only |
| `ApplicationSettingsService` | Branding icons → media; cached `mediaUrls()` |
| `CartService` / `CheckoutService` | Resolve product image from media URLs |

---

## G. Controllers / middleware / requests

| File | Change |
| --- | --- |
| `Settings/ProfileController` | Avatar sync via media; props `avatar` / `avatar_url` from media |
| `HandleInertiaRequests` | Shared user `avatar` → `avatarUrl()` |
| `Platform/AdminController` | Avatar props from media |
| `Platform/BranchController` | Logo props from media |
| `Branch/GeneralSettingsController` | Logo props from media |
| `Platform/GeneralSettingsController` | `*_url` only (no `*_path`) |
| `Platform/HomeBannerController` | `hasMedia` for activate checks |
| `Platform|Branch` Category/Product/Pack controllers | Image/gallery from media |
| `Storefront/ProductController` | Media URLs |
| `Platform/WholesaleInquiryController` | Product image from media |
| `Branch|Platform DriverProductAssignController` | Product image from media |
| `UpdateHomeBannerRequest` | Image presence via media |
| `UpdateProductRequest` / `UpdateBranchProductRequest` / `UpdatePackRequest` | `remove_gallery_ids` scoped to `media` table / gallery collection |
| `routes/channels.php` | Presence avatar → `avatarUrl()` |

---

## H. Support / payloads / DataTables

| File | Change |
| --- | --- |
| `MediaUrl` | Added `fromCollection(HasMedia, collection)` |
| `DriverPayload` | Avatar + vehicle from media |
| `WorkforcePayload` | Avatar URLs from media |
| `ConversationPayload` | Avatar URLs from media |
| `BranchProductShowPayload` / `PackPortalPayload` | Media-based images |
| `DriverPlaceOrderCatalog` / `StorefrontCatalog` | Media-based images |
| `PlaceOrderAction` | Snapshot stores resolved media URL |
| DataTables (`Product`, `Category`, `Pack`, `Branch`, `Driver`, `Staff`, `Admin`, …) | `image_url` / `avatar_url` / `logo_url` from media |

---

## I. Factories

| Factory | Change |
| --- | --- |
| `BranchFactory` | Removed `logo` |
| `CategoryFactory` | Removed `image` |
| `ProductFactory` | Removed `image` |
| `PackFactory` | Removed `image` |
| `DriverProfileFactory` | Removed `vehicle_image_path` |

---

## J. Seeders

| Seeder / concern | Change |
| --- | --- |
| `SeedsCatalogMedia` | Added `attachSeedMedia()` (idempotent, `seed_source` custom property) |
| `CategorySeeder` / `ProductSeeder` | Attach media instead of writing path columns |
| `ExtendedCategorySeeder` / `ExtendedProductSeeder` | Same |
| `BranchSeeder` / `NationwideBranchSeeder` | Attach storefront logo into `logo` collection |

---

## K. Frontend (minimal)

| File | Change |
| --- | --- |
| `resources/js/pages/platform/settings/general.tsx` | Dropped `*_path` props; uses `*_url` only |
| `resources/js/pages/platform/home-banners/form.tsx` | Works without `image_path` |
| `resources/js/components/platform/cms/cms-payload-fields.tsx` | Media URL / remove flags without path columns |

Existing `FileUpload` kept; no new Spatie React widgets.

---

## L. Tests updated / added

**Updated** (assert `hasMedia` + WebP mime instead of path/`Storage::assertExists`):

- `ProfileUpdateTest`, `CustomerAccountTest`
- `AdminManagementTest`, `BranchStaffManagementTest`
- `DriverManagementTest`, `DriverProfileTest`
- `BranchManagementTest`, `BranchGeneralSettingsTest`
- `CategoryImageTest`, `ProductImageGalleryTest`
- `BranchProductManagementTest`, `BranchPacksSavesPersistTest`
- `HomeBannersTest`, `CmsAndContactsTest`, `ApplicationSettingsTest`
- `CatalogSeederImagePathTest`, `PackDetailPageTest`, `StorefrontCartTest`, `PublicPagesTest`

**Added**

- `MediaUploadServiceTest`
- `MigrateLegacyPathsCommandTest`

---

## M. AI rules

| File | Change |
| --- | --- |
| `.ai/rules/migrations.md` | Pre-handover: image path columns removed on create migrations; no Schema::table drops |
| `.ai/rules/concerns-support.md` | Raster → WebP via `MediaUploadService`; SVG skipped; conversation attachments excluded |
| `.ai/rules/index.md` | Index entry for new rule |

---

## N. Incidental (not media feature — Boost auto-update)

Composer post-install ran `boost:update`, which touched:

- `.agents/skills/**`, `.cursor/skills/**`, `AGENTS.md`, `boost.json`

These are skill/guideline refreshes, not media business logic.

---

## O. Explicit non-changes

- Conversation `MessageAttachment` / chat files still path + disk
- No client-side WebP conversion in the browser
- No new shadcn upload components
- No post-handover `Schema::table` drop migrations

---

## P. Collection map (quick reference)

```
User.avatar
Branch.logo
DriverProfile.vehicle
Category.image
Product.image + Product.gallery
Pack.image + Pack.gallery
HomeBanner.image
CmsPage.hero + CmsPage.media
ApplicationSetting.logo | favicon | pwa_192 | pwa_512 | pwa_maskable | apple_touch
```

---

## Q. How to verify locally

```bash
php artisan migrate:fresh --seed
php artisan test --compact
# optional: attach seed logos/banners if collections empty
php artisan media:migrate-legacy-paths
php artisan media:migrate-legacy-paths --dry-run
```
