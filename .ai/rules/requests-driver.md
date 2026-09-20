---
paths:
  - 'app/Http/Requests/Driver/**'
---

# Requests Driver

## Driver self-edit photos have no max size
Driver self-edit avatar and vehicle_image stay image + mimes (jpg,jpeg,png,webp) with no max size. Do not restore max:2048 or a client 2MB check on /driver/profile/edit. PHP/nginx upload limits still apply at the server.
