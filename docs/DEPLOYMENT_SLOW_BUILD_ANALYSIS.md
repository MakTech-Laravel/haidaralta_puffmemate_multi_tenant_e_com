# Coolify Deployment Slow-Build Analysis

**Bangla version:** [`DEPLOYMENT_SLOW_BUILD_ANALYSIS.bn.md`](./DEPLOYMENT_SLOW_BUILD_ANALYSIS.bn.md)

**Remediation (implemented):** Self-contained multi-stage `Dockerfile` (`runtime-base` → `build` → `production`) for Coolify **git auto-deploy**. Reusable names `php-runtime` / `app`. No Docker GHCR workflows. `docker/nginx|entrypoint|supervisord` unchanged. See [`COOLIFY_PRODUCTION_DEPLOYMENT.md`](./COOLIFY_PRODUCTION_DEPLOYMENT.md).

**Report date:** 2026-10-22  
**Source log:** `deployment-vntsivumgzidpsn2gd6ng8k3-all-logs-2026-10-22-06-03-27.txt`  
**Application:** `MakTech-Laravel/haidaralta_puffonmate_multi_tenant_e_com`  
**Branch / commit:** `production` @ `81369f2ef5f85972b57439e7498dcb403b848e0e`  
**Target:** Coolify `localhost` (Docker 29.5.3 + BuildKit/Buildx)  
**Build pack:** Dockerfile (root `Dockerfile`)

---

## 1. Executive summary

| Question | Answer |
| --- | --- |
| Why do deploys often take 45–60+ minutes? | Cold or near-cold Docker builds recompile PHP extensions, reinstall apt packages, reinstall Composer/pnpm deps, then still pay a large Vite + image-export cost. |
| Was *this* logged deploy a cold build? | **No.** It was a **warm-cache** deploy (~12 minutes). Early expensive layers were `CACHED`. |
| What still dominates even when cache hits? | (1) Frontend build (~3.5 min), (2) copying `/var/www` into production (~1.2 min), (3) exporting + unpacking the final image (~3.6 min). |
| Is switching to docker-compose the fix? | **No.** Coolify already uses compose only for the rolling update. Compose does not remove compile/export cost. |
| Biggest security finding in this log? | Coolify injected **runtime secrets as Docker `ARG`/`ENV` during build** (`APP_KEY`, `DB_PASSWORD`, `REDIS_PASSWORD`, AWS keys, etc.) — **38** `SecretsUsedInArgOrEnv` warnings. |

**Bottom line:** The Dockerfile design + Coolify on-server build + fragile layer cache explain 45–60 minute deploys. This specific log proves that even a “good” cached run still spends ~12 minutes, mostly on Vite (React Compiler) and huge image layer export/unpack.

---

## 2. This deployment — wall-clock timeline

| Time (UTC) | Event | Duration |
| --- | --- | --- |
| 11:34:33 | Deploy start (helper + Docker detected) | — |
| 11:34:45 | Git shallow clone of `production` | ~40s total prep |
| 11:35:24–26 | Base image resolve + context transfer **33.34 MB** | ~2s context |
| 11:35:26 | Build layers 2–9: PHP ext compile, Composer, Node copy, pnpm | **ALL CACHED** |
| 11:35:26–29 | `COPY . .` | 2.8s |
| 11:35:29–11:38:57 | `composer dump-autoload` + PWA assets + `pnpm run build` | **207.9s (~3.5 min)** |
| 11:38:57–11:40:23 | Gap before production stage (layer finalize / scheduling) | **~86s** |
| 11:40:23 | Production apt / PHP ext copy | **CACHED** |
| 11:40:23–11:41:37 | `COPY --from=build … /var/www` (`chown=www-data`) | **74.1s** |
| 11:41:41–11:43:42 | `exporting layers` | **120.8s** |
| 11:43:42–11:45:18 | `unpacking` final image tag | **95.7s** |
| 11:45:18 | Export stage total | **217.1s (~3.6 min)** |
| 11:45:20 | Image build completed | — |
| 11:45:24–11:46:20 | Rolling update + healthcheck + old container stop | ~1 min |
| 11:46:21–27 | Post-deploy: `storage:link`, permissions | ~6s |
| 11:46:31 | Helper stopped | — |

**Total wall time for this log: ~12 minutes** (`11:34:33` → `11:46:31`).

That matches Coolify history rows around ~10–14 minutes (warm), **not** the 40–53 minute cold runs.

---

## 3. Layer cache status (this log)

| Step | Dockerfile action | Result |
| --- | --- | --- |
| `#14` build 2/11 | `apt` + `docker-php-ext-install` + `pecl redis` | **CACHED** |
| `#11` | Copy Composer binary | **CACHED** |
| `#15` | `COPY --from=node:24 … /usr/local/` | **CACHED** |
| `#13` | `composer install --no-dev` | **CACHED** |
| `#17` | `corepack` + `pnpm install --frozen-lockfile` | **CACHED** |
| `#18` | `COPY . .` | Ran (2.8s) — **cache busted by code** |
| `#19` | dump-autoload + PWA + Vite | Ran (207.9s) |
| `#20` | Production apt + PGDG `postgresql-client-18` | **CACHED** |
| `#21–22` | Copy PHP extensions/conf | **CACHED** |
| `#23` | Copy built app into production | Ran (74.1s) |
| `#31` | Export + unpack image | Ran (217.1s) |

### Why history still shows 45–60 minutes

When `#14` / `#20` / `#13` / `#17` are **not** cached (Dockerfile change, base image digest change, lockfile change, BuildKit cache pruned, disk pressure), add approximately:

| Cold work | Estimated added time on a typical Coolify VPS |
| --- | --- |
| PHP extensions compile (`intl`, `gd`+webp, `pdo_*`, `pecl redis`, …) | **12–25 min** |
| Production apt + PGDG Postgres 18 client | **4–10 min** |
| `composer install` | **2–5 min** |
| `pnpm install` | **3–8 min** |
| **Warm baseline (this log)** | **~12 min** |
| **Cold total** | **~35–60+ min** |

That fully explains the Coolify dashboard variance (9 min vs 53 min for similar merge commits).

---

## 4. Frontend build deep dive (`#19`, 207.9s)

Breakdown inside the single `RUN` that always invalidates on code change:

| Sub-step | Approx time | Evidence |
| --- | --- | --- |
| `composer dump-autoload --classmap-authoritative` | ~10s | 11,000 classes generated |
| `pnpm generate:pwa-assets` | ~40s (startup → ready logged at 48.9s into step) | Dozens of Apple splash PNGs generated every deploy |
| Wayfinder type generation | 7.8s | Plugin timings |
| Vite transform (4404 modules) | ~131s (64.5s → 195.4s into step) | `✓ 4404 modules transformed` |
| Render chunks + gzip report | ~11s | — |

### Plugin cost (from BuildKit / Rolldown plugin timings)

| Plugin | Share | Time | Calls |
| --- | --- | --- | --- |
| `@rolldown/plugin-babel` (React Compiler) | **80%** | **119.7s** | 742 |
| `vite:css` transform | 36%* | 53.9s | 3 |
| `@laravel/vite-plugin-wayfinder` | 5% | 7.8s | 1 |
| `@tailwindcss/vite` | 1% | 1.4s | 3 |

\*Plugin timing percentages can overlap; Babel is clearly the dominant CPU cost.

### Bundle / asset signals

- **~1,894** JS asset lines emitted in the log (very large chunk graph).
- `public/build/manifest.json` ≈ **945 KB** (64 KB gzip).
- Oversized chunks called out by Vite:
  - `app-*.js` **886 KB** (240 KB gzip)
  - `edit-*.js` **451 KB**
  - `order-reports-*.js` **400 KB**
  - `jsx-runtime-*.js` **340 KB**
  - `icon-catalog-*.js` **192 KB**
  - `leaflet-src-*.js` **149 KB**
- Warning: `resources/js/lib/fcm.ts` is both dynamically and statically imported → ineffective code-splitting.
- Warning: chunks > 500 KB after minify.

**Conclusion:** Even with perfect Docker layer cache, every code deploy pays ~3.5 minutes for PWA regeneration + React Compiler Babel + a very large Inertia/React graph.

---

## 5. Image packaging cost (often overlooked)

Even with apt/PHP **CACHED**, this deploy spent:

| Step | Time | Why it hurts |
| --- | --- | --- |
| `COPY --from=build --chown=www-data:www-data /var/www /var/www` | **74.1s** | Copies entire app + `vendor` + `node_modules` build tree leftovers + `public/build` while chowning |
| `exporting layers` | **120.8s** | Large final image layers written |
| `unpacking` to local tag | **95.7s** | Disk-bound extract on the Coolify host |
| Gap after `#19` | **~86s** | Intermediate layer commit / BuildKit scheduling |

**Subtotal packaging overhead on a warm build: ~6+ minutes.**

This is why “cache hit” deploys are still far from a 2–3 minute release.

Likely contributors to image weight:

- Build stage installs full Node under `/usr/local` inside the PHP image (even if final stage does not keep Node, the build stage is huge and slow to materialize when uncached).
- Production copies all of `/var/www` from build (includes `vendor`, built assets, and anything not pruned).
- Production installs `nano`, full nginx/supervisor stack, and `postgresql-client-18` (needed for Spatie backups — keep client, drop `nano`).

---

## 6. Security findings (critical)

Build finished with:

> `38 warnings found` — `SecretsUsedInArgOrEnv`

Coolify injected sensitive values as Docker build `ARG` / `ENV`, including at least:

- `APP_KEY`
- `DB_PASSWORD`
- `REDIS_PASSWORD`
- `MAIL_PASSWORD`
- `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`
- `REVERB_APP_SECRET` / `REVERB_APP_KEY`
- `MEILISEARCH_KEY`
- `BACKUP_ARCHIVE_PASSWORD`
- `FIREBASE_CREDENTIALS`
- plus many `VITE_*` keys (public by design, but mixed with real secrets)

### Why this matters

1. **Secrets can end up in image history / build cache metadata** even if not used by Dockerfile instructions.
2. **Cache instability:** changing any injected build-arg/env can invalidate layers and force cold rebuilds (contributes to 45–60 minute outliers).
3. **Wrong trust boundary:** only public `VITE_*` values should be build-args. Runtime secrets must be Coolify runtime env only (already documented in `docs/COOLIFY_PRODUCTION_DEPLOYMENT.md`).

### Required Coolify configuration fix

- Disable “pass all environment variables as build arguments” (or equivalent).
- Allowlist **only** public Vite build args already declared in `Dockerfile`.
- Keep `APP_KEY`, DB/Redis/AWS/mail/Firebase Admin secrets as **runtime-only**.

---

## 7. Root-cause ranking

| Rank | Cause | Impact when cold | Impact in this warm log |
| --- | --- | --- | --- |
| 1 | PHP extensions compiled from source in app Dockerfile | 12–25 min | 0 (cached) |
| 2 | No durable remote/CI build cache; host prune → cold rebuilds | Turns 12 min → 45–60 min | Explains historical variance |
| 3 | Huge final image export/unpack on Coolify disk | 3–6+ min | **~3.6 min export + 74s copy** |
| 4 | React Compiler Babel + large Vite graph every code change | 3–8+ min | **~3.5 min (#19)** |
| 5 | Production apt + PGDG client on cache miss | 4–10 min | 0 (cached) |
| 6 | `COPY --from=node /usr/local/` mega-layer | 3–8 min when uncached | 0 (cached) |
| 7 | PWA splash generation every build | ~30–60s | ~40s |
| 8 | Secrets injected as build args | Cache bust + security risk | 38 warnings |
| — | docker-compose vs Dockerfile pack | Negligible | Coolify already uses compose for rollout |

---

## 8. What docker-compose will *not* fix

Coolify already ran:

```text
docker compose … up --build -d
```

for the rolling update. Moving the project “to docker-compose” as the primary build definition does **not**:

- skip PHP extension compilation
- shrink Vite/Babel cost
- avoid exporting multi‑hundred‑MB layers
- give durable cache across prune

Compose is orchestration. Speed comes from **image strategy**.

---

## 9. Recommended solution (best / secure / advanced)

### Phase A — immediate (config + small Dockerfile hygiene)

1. Coolify: **stop injecting runtime secrets as build args**; allowlist only `VITE_*` public args.
2. Remove `nano` from production image.
3. Drop `pdo_mysql` if production is Postgres-only (less compile work on cold builds).
4. Commit generated PWA icons/splashes (or narrow generator to required sizes) so builds skip ~40s + fewer files in `/var/www`.
5. Avoid `COPY --chown` on the entire `/var/www` tree; copy then `chown` only `storage` + `bootstrap/cache` (often faster, smaller dirty metadata).

**Expected:** fewer accidental cold builds; warm path maybe −1–2 minutes.

### Phase B — high ROI Dockerfile architecture

1. Publish a **prebuilt base image** (e.g. `ghcr.io/<org>/puffmemate-php:8.4-exts`) that already contains PHP 8.4-FPM + all extensions + redis + runtime libs + nginx/supervisor/postgresql-client-18.
2. App `Dockerfile` becomes:
   - `FROM` base (no compile)
   - Composer stage with BuildKit cache mount
   - **Separate** `node:24` frontend stage (do **not** copy Node into PHP)
   - Slim final stage: app files + `public/build` only
3. Add BuildKit caches:

```dockerfile
RUN --mount=type=cache,target=/root/.composer/cache composer install …
RUN --mount=type=cache,target=/root/.local/share/pnpm/store pnpm install …
```

**Expected warm code deploy:** ~6–10 min → target **~4–7 min**.  
**Expected cold (base already pulled):** ~10–18 min instead of 45–60.

### Phase C — gold path (advanced)

1. GitHub Actions (or Coolify CI on a build host) builds and pushes immutable tags to GHCR.
2. Coolify Application deploys **prebuilt image** (no on-server compile).
3. Rolling update becomes pull + healthcheck + swap.

**Expected deploy time:** **~2–5 minutes**.

### Phase D — frontend performance (parallel)

1. Keep React Compiler for prod quality, but evaluate compile-only on changed files / CI caching of Vite prebundles if tooling allows.
2. Fix ineffective dynamic import of `fcm.ts`.
3. Split `app` / editor / reports / leaflet / icon-catalog more aggressively (`import()`), reduce 500KB+ chunks.
4. Consider not running Wayfinder generation inside Docker if types are committed from CI.

---

## 10. Target SLOs

| Scenario | Current (observed / inferred) | Target after Phase B | Target after Phase C |
| --- | --- | --- | --- |
| Warm code-only deploy | ~12 min (this log) | 4–7 min | 2–5 min (pull-only) |
| Cold full rebuild | 35–60+ min | 10–18 min | Rare (base rebuild only) |
| Dockerfile.base change | N/A | 15–30 min once | Same, infrequent |

---

## 11. Appendix — key log excerpts (sanitized)

### Cache hits on the expensive compile layer

```text
#14 [build  2/11] RUN apt-get … docker-php-ext-install … pecl install redis …
#14 CACHED
#13 [build  7/11] RUN composer install …
#13 CACHED
#17 [build  9/11] RUN … pnpm install --frozen-lockfile
#17 CACHED
```

### Frontend build

```text
#19 … pnpm generate:pwa-assets && pnpm run build
✓ 4404 modules transformed.
@rolldown/plugin-babel transform (80%, 119.7s, 742 calls)
#19 DONE 207.9s
```

### Image export

```text
#23 COPY --from=build --chown=www-data:www-data /var/www /var/www
#23 DONE 74.1s
#31 exporting layers 120.8s done
#31 unpacking … 95.7s done
#31 DONE 217.1s
```

### Secret injection warnings (sample)

```text
38 warnings found
SecretsUsedInArgOrEnv: … ARG "APP_KEY"
SecretsUsedInArgOrEnv: … ARG "DB_PASSWORD"
SecretsUsedInArgOrEnv: … ARG "REDIS_PASSWORD"
SecretsUsedInArgOrEnv: … ARG "AWS_SECRET_ACCESS_KEY"
SecretsUsedInArgOrEnv: … ARG "REVERB_APP_SECRET"
```

---

## 12. Next implementation step

Recommended order:

1. Fix Coolify build-arg allowlist (security + cache stability) — same day.
2. Implement `Dockerfile.base` + slim multi-stage app Dockerfile — highest deploy-time ROI.
3. Optionally move to registry-based Coolify deploys for 2–5 minute releases.

This report is analysis-only; no production Dockerfile changes were applied as part of writing it.
