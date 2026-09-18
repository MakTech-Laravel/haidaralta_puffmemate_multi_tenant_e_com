---
paths:
    - package.json
    - vite.config.ts
---

# General

## Use pnpm only for JS deps

This app uses **pnpm** (`pnpm-lock.yaml`, `pnpm-workspace.yaml`, `packageManager: pnpm@…`). Keep those files.

Do not mix package managers:

- Install / add deps with `pnpm install` / `pnpm add …` / `pnpm dlx shadcn@latest add …`
- Do not run `npm install` in this repo (it creates `package-lock.json` and a non-pnpm `node_modules` layout). If `package-lock.json` appears, delete it and re-run `pnpm install`.
- Before `pnpm add` / shadcn on Windows: stop `php artisan serve`, Vite (`pnpm dev`), and anything locking `node_modules` — otherwise you get `EPERM` rename errors.
- Composer scripts (`setup`, `ci:check`) call `pnpm`, not `npm`.

## Wayfinder generate needs --with-form

Always run `php artisan wayfinder:generate --with-form` (never bare generate). Without --with-form, .form() helpers disappear and Inertia pages crash with TypeError: *.form is not a function. Keep vite wayfinder({ form: true }) if that option exists.
