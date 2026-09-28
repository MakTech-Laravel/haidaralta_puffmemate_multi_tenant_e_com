---
paths:
  - 'app/Support/MediaUrl.php,database/seeders/Concerns/SeedsCatalogMedia.php,resources/js/components/storefront/{cart,checkout}/**'
---

# Cartcheckout

## Cart images must be real image files
MediaUrl::resolve only returns a public /images path when the file has a real image header. 16-byte HTTP-fake stubs like fake-image-bytes fall back to /images/storefront/logo.png. CatalogSeeder must not write those stubs into public/images. Cart, checkout, and the header sheet render through StorefrontProductImage with an onError logo fallback.
