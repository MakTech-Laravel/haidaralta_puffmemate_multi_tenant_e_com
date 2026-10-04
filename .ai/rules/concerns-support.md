---
paths:
  - 'app/Services/Media/**,app/Models/Concerns/InteractsWithAppMedia.php,app/Support/MediaUrl.php'
---

# Concerns Support

## Raster uploads go through MediaUploadService as WebP
All raster image uploads (jpg/png/gif/webp) must go through App\Services\Media\MediaUploadService, which stores originals as WebP. SVG stays SVG (no WebP conversion). Models implement HasMedia + InteractsWithAppMedia collections. Do not add path-column image storage. Conversation MessageAttachment and other non-image files stay path/disk based. Image optimizer binaries may be missing in local/CI — MediaUploadService swallows optimizer failures.
