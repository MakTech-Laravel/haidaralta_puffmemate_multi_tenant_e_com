---
paths:
  - 'app/Http/Requests/Platform/*Category*Request.php'
---

# Requests Platform

## Category images use ValidatesCatalogProductImages
Store/Update CategoryRequest image rules use catalogImageFileRules() (jpg/jpeg/png/webp/svg, max 2MB, image:allow_svg). Serve SVG only as img src.
