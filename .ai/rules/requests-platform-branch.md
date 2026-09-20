---
paths:
    - 'app/Http/Requests/{Platform,Branch}/*Product*Request.php'
---

# Requests Platform Branch

## Product images allow secure SVG set

Product primary/gallery images allow jpg,jpeg,png,webp,svg via ValidatesCatalogProductImages (image:allow_svg + mimes, max 2MB). Serve SVG only as <img src>, never inline. Frontend accept must include image/svg+xml and .svg.
