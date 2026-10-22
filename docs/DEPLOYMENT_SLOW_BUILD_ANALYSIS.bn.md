# Coolify ডিপ্লয়মেন্ট স্লো-বিল্ড অ্যানালাইসিস

**ইংরেজি ভার্সন:** [`DEPLOYMENT_SLOW_BUILD_ANALYSIS.md`](./DEPLOYMENT_SLOW_BUILD_ANALYSIS.md)

**রিমেডিয়েশন (ইমপ্লিমেন্টেড):** Coolify **git auto-deploy**-এর জন্য self-contained multi-stage `Dockerfile` (`runtime-base` → `build` → `production`)। জেনেরিক নাম `php-runtime` / `app`। Docker GHCR workflow নেই। `docker/nginx|entrypoint|supervisord` অপরিবর্তিত। [`COOLIFY_PRODUCTION_DEPLOYMENT.md`](./COOLIFY_PRODUCTION_DEPLOYMENT.md)।

**রিপোর্ট তারিখ:** ২০২৬-১০-২২  
**সোর্স লগ:** `deployment-vntsivumgzidpsn2gd6ng8k3-all-logs-2026-10-22-06-03-27.txt`  
**অ্যাপ্লিকেশন:** `MakTech-Laravel/haidaralta_puffonmate_multi_tenant_e_com`  
**ব্রাঞ্চ / কমিট:** `production` @ `81369f2ef5f85972b57439e7498dcb403b848e0e`  
**টার্গেট:** Coolify `localhost` (Docker 29.5.3 + BuildKit/Buildx)  
**বিল্ড প্যাক:** Dockerfile (রুট `Dockerfile`)

---

## ১. সারসংক্ষেপ (Executive summary)

| প্রশ্ন | উত্তর |
| --- | --- |
| কেন ডিপ্লয় প্রায়ই ৪৫–৬০+ মিনিট লাগে? | Cold বা near-cold Docker বিল্ডে PHP extension আবার compile হয়, apt প্যাকেজ আবার ইনস্টল হয়, Composer/pnpm deps আবার ইনস্টল হয়, তারপরও বড় Vite + image-export খরচ থাকে। |
| *এই* লগ করা ডিপ্লয় কি cold build ছিল? | **না।** এটা ছিল **warm-cache** ডিপ্লয় (~১২ মিনিট)। আগের ব্যয়বহুল layerগুলো `CACHED` ছিল। |
| Cache hit হলেও কী সবচেয়ে বেশি সময় নেয়? | (১) Frontend build (~৩.৫ মিনিট), (২) production-এ `/var/www` কপি (~১.২ মিনিট), (৩) ফাইনাল image export + unpack (~৩.৬ মিনিট)। |
| docker-compose-এ স্যুইচ করলে সমাধান হবে? | **না।** Coolify ইতিমধ্যে শুধু rolling update-এর জন্য compose ব্যবহার করে। Compose compile/export খরচ কাটায় না। |
| এই লগে সবচেয়ে বড় সিকিউরিটি ফাইন্ডিং কী? | Coolify **runtime secretগুলো build-এর সময় Docker `ARG`/`ENV` হিসেবে inject** করেছে (`APP_KEY`, `DB_PASSWORD`, `REDIS_PASSWORD`, AWS keys ইত্যাদি) — **৩৮টি** `SecretsUsedInArgOrEnv` ওয়ার্নিং। |

**মূল কথা:** Dockerfile ডিজাইন + Coolify সার্ভারে on-server build + দুর্বল layer cache—এগুলোই ৪৫–৬০ মিনিট ডিপ্লয়ের কারণ। এই নির্দিষ্ট লগ প্রমাণ করে, “ভালো” cached রানও ~১২ মিনিট নেয়—মূলত Vite (React Compiler) এবং বিশাল image layer export/unpack-এর জন্য।

---

## ২. এই ডিপ্লয়মেন্ট — ওয়াল-ক্লক টাইমলাইন

| সময় (UTC) | ঘটনা | সময়কাল |
| --- | --- | --- |
| 11:34:33 | ডিপ্লয় শুরু (helper + Docker detected) | — |
| 11:34:45 | `production`-এর Git shallow clone | মোট prep ~৪০ সেকেন্ড |
| 11:35:24–26 | Base image resolve + context transfer **৩৩.৩৪ MB** | context ~২ সেকেন্ড |
| 11:35:26 | Build layer ২–৯: PHP ext compile, Composer, Node copy, pnpm | **সব CACHED** |
| 11:35:26–29 | `COPY . .` | ২.৮ সেকেন্ড |
| 11:35:29–11:38:57 | `composer dump-autoload` + PWA assets + `pnpm run build` | **২০৭.৯ সেকেন্ড (~৩.৫ মিনিট)** |
| 11:38:57–11:40:23 | Production stage-এর আগে ফাঁকা (layer finalize / scheduling) | **~৮৬ সেকেন্ড** |
| 11:40:23 | Production apt / PHP ext copy | **CACHED** |
| 11:40:23–11:41:37 | `COPY --from=build … /var/www` (`chown=www-data`) | **৭৪.১ সেকেন্ড** |
| 11:41:41–11:43:42 | `exporting layers` | **১২০.৮ সেকেন্ড** |
| 11:43:42–11:45:18 | ফাইনাল image tag `unpacking` | **৯৫.৭ সেকেন্ড** |
| 11:45:18 | Export stage মোট | **২১৭.১ সেকেন্ড (~৩.৬ মিনিট)** |
| 11:45:20 | Image build সম্পন্ন | — |
| 11:45:24–11:46:20 | Rolling update + healthcheck + পুরনো container stop | ~১ মিনিট |
| 11:46:21–27 | Post-deploy: `storage:link`, permissions | ~৬ সেকেন্ড |
| 11:46:31 | Helper বন্ধ | — |

**এই লগের মোট সময়: ~১২ মিনিট** (`11:34:33` → `11:46:31`)।

এটা Coolify হিস্টরির ~১০–১৪ মিনিটের (warm) রানের সাথে মিলে—**৪০–৫৩ মিনিটের cold রানের সাথে নয়**।

---

## ৩. Layer cache স্ট্যাটাস (এই লগ)

| স্টেপ | Dockerfile অ্যাকশন | ফলাফল |
| --- | --- | --- |
| `#14` build 2/11 | `apt` + `docker-php-ext-install` + `pecl redis` | **CACHED** |
| `#11` | Composer বাইনারি কপি | **CACHED** |
| `#15` | `COPY --from=node:24 … /usr/local/` | **CACHED** |
| `#13` | `composer install --no-dev` | **CACHED** |
| `#17` | `corepack` + `pnpm install --frozen-lockfile` | **CACHED** |
| `#18` | `COPY . .` | চলেছে (২.৮স) — **কোডে cache bust** |
| `#19` | dump-autoload + PWA + Vite | চলেছে (২০৭.৯স) |
| `#20` | Production apt + PGDG `postgresql-client-18` | **CACHED** |
| `#21–22` | PHP extensions/conf কপি | **CACHED** |
| `#23` | Built app production-এ কপি | চলেছে (৭৪.১স) |
| `#31` | Image export + unpack | চলেছে (২১৭.১স) |

### হিস্টরিতে এখনও কেন ৪৫–৬০ মিনিট দেখায়

যখন `#14` / `#20` / `#13` / `#17` **cache থাকে না** (Dockerfile পরিবর্তন, base image digest পরিবর্তন, lockfile পরিবর্তন, BuildKit cache prune, ডিস্ক প্রেশার), আনুমানিক যোগ হয়:

| Cold কাজ | সাধারণ Coolify VPS-এ আনুমানিক অতিরিক্ত সময় |
| --- | --- |
| PHP extension compile (`intl`, `gd`+webp, `pdo_*`, `pecl redis`, …) | **১২–২৫ মিনিট** |
| Production apt + PGDG Postgres 18 client | **৪–১০ মিনিট** |
| `composer install` | **২–৫ মিনিট** |
| `pnpm install` | **৩–৮ মিনিট** |
| **Warm baseline (এই লগ)** | **~১২ মিনিট** |
| **Cold মোট** | **~৩৫–৬০+ মিনিট** |

Coolify ড্যাশবোর্ডের তারতম্য (একই ধরনের merge কমিটে ৯ মিনিট vs ৫৩ মিনিট) এতেই পুরোপুরি ব্যাখ্যা হয়।

---

## ৪. Frontend build গভীর বিশ্লেষণ (`#19`, ২০৭.৯স)

কোড পরিবর্তনে যে একক `RUN` সবসময় invalidate হয়, তার ভিতরের ভাগ:

| সাব-স্টেপ | আনুমানিক সময় | প্রমাণ |
| --- | --- | --- |
| `composer dump-autoload --classmap-authoritative` | ~১০স | ১১,০০০ class জেনারেট |
| `pnpm generate:pwa-assets` | ~৪০স (স্টেপের ৪৮.৯স-এ ready লগ) | প্রতি ডিপ্লয়ে ডজনখানেক Apple splash PNG |
| Wayfinder type generation | ৭.৮স | Plugin timings |
| Vite transform (৪৪০৪ module) | ~১৩১স (স্টেপের ৬৪.৫স → ১৯৫.৪স) | `✓ 4404 modules transformed` |
| Render chunks + gzip report | ~১১স | — |

### Plugin খরচ (BuildKit / Rolldown plugin timings থেকে)

| Plugin | শেয়ার | সময় | কল |
| --- | --- | --- | --- |
| `@rolldown/plugin-babel` (React Compiler) | **৮০%** | **১১৯.৭স** | ৭৪২ |
| `vite:css` transform | ৩৬%* | ৫৩.৯স | ৩ |
| `@laravel/vite-plugin-wayfinder` | ৫% | ৭.৮স | ১ |
| `@tailwindcss/vite` | ১% | ১.৪স | ৩ |

\*Plugin timing শতাংশ overlap করতে পারে; Babel স্পষ্টতই প্রধান CPU খরচ।

### Bundle / asset সিগন্যাল

- লগে **~১,৮৯৪** JS asset লাইন (খুব বড় chunk graph)।
- `public/build/manifest.json` ≈ **৯৪৫ KB** (gzip ৬৪ KB)।
- Vite যে oversized chunk ধরেছে:
  - `app-*.js` **৮৮৬ KB** (gzip ২৪০ KB)
  - `edit-*.js` **৪৫১ KB**
  - `order-reports-*.js` **৪০০ KB**
  - `jsx-runtime-*.js` **৩৪০ KB**
  - `icon-catalog-*.js` **১৯২ KB**
  - `leaflet-src-*.js` **১৪৯ KB**
- ওয়ার্নিং: `resources/js/lib/fcm.ts` একসাথে dynamic ও static import → code-splitting কার্যকর নয়।
- ওয়ার্নিং: minify-এর পর chunk > ৫০০ KB।

**সিদ্ধান্ত:** Docker layer cache পারফেক্ট থাকলেও, প্রতি কোড ডিপ্লয়ে ~৩.৫ মিনিট যায় PWA regenerate + React Compiler Babel + বড় Inertia/React graph-এ।

---

## ৫. Image packaging খরচ (প্রায়ই উপেক্ষিত)

apt/PHP **CACHED** থাকা সত্ত্বেও এই ডিপ্লয়ে খরচ:

| স্টেপ | সময় | কেন ক্ষতি করে |
| --- | --- | --- |
| `COPY --from=build --chown=www-data:www-data /var/www /var/www` | **৭৪.১স** | পুরো app + `vendor` + `node_modules` অবশিষ্টাংশ + `public/build` chown সহ কপি |
| `exporting layers` | **১২০.৮স** | বড় ফাইনাল image layer লেখা |
| লোকাল ট্যাগে `unpacking` | **৯৫.৭স** | Coolify হোস্টে ডিস্ক-বাউন্ড extract |
| `#19`-এর পর ফাঁকা | **~৮৬স** | Intermediate layer commit / BuildKit scheduling |

**Warm build-এ packaging ওভারহেড সাবটোটাল: ~৬+ মিনিট।**

তাই “cache hit” ডিপ্লয়ও ২–৩ মিনিট রিলিজের কাছাকাছি আসে না।

Image ওজনের সম্ভাব্য কারণ:

- Build stage PHP image-এর ভিতরে `/usr/local`-এ পুরো Node ইনস্টল করে (ফাইনাল stage-এ Node না থাকলেও, uncached হলে build stage বিশাল ও ধীর)।
- Production build থেকে পুরো `/var/www` কপি করে (`vendor`, built assets, prune না হওয়া সব কিছু)।
- Production-এ `nano`, পুরো nginx/supervisor স্ট্যাক, এবং `postgresql-client-18` (Spatie backup-এর জন্য client রাখুন, `nano` বাদ দিন)।

---

## ৬. সিকিউরিটি ফাইন্ডিং (ক্রিটিক্যাল)

বিল্ড শেষে:

> `38 warnings found` — `SecretsUsedInArgOrEnv`

Coolify সংবেদনশীল মান Docker build `ARG` / `ENV` হিসেবে inject করেছে, অন্তত:

- `APP_KEY`
- `DB_PASSWORD`
- `REDIS_PASSWORD`
- `MAIL_PASSWORD`
- `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`
- `REVERB_APP_SECRET` / `REVERB_APP_KEY`
- `MEILISEARCH_KEY`
- `BACKUP_ARCHIVE_PASSWORD`
- `FIREBASE_CREDENTIALS`
- সাথে অনেক `VITE_*` কী (ডিজাইন অনুযায়ী পাবলিক, কিন্তু আসল secret-এর সাথে মিশে আছে)

### কেন এটা গুরুত্বপূর্ণ

1. **Secret image history / build cache metadata-তে যেতে পারে**—Dockerfile instruction-এ ব্যবহার না করলেও।
2. **Cache অস্থিরতা:** যেকোনো injected build-arg/env পরিবর্তন layer invalidate করে cold rebuild বাধ্য করতে পারে (৪৫–৬০ মিনিট outlier-এ অবদান)।
3. **ভুল trust boundary:** শুধু পাবলিক `VITE_*` মান build-arg হওয়া উচিত। Runtime secret শুধু Coolify runtime env-এ থাকবে (ইতিমধ্যে `docs/COOLIFY_PRODUCTION_DEPLOYMENT.md`-এ ডকুমেন্টেড)।

### প্রয়োজনীয় Coolify কনফিগ ফিক্স

- “সব environment variable-কে build argument হিসেবে পাঠাও” (বা সমতুল্য) বন্ধ করুন।
- Allowlist-এ **শুধু** `Dockerfile`-এ ঘোষিত পাবলিক Vite build arg রাখুন।
- `APP_KEY`, DB/Redis/AWS/mail/Firebase Admin secret **runtime-only** রাখুন।

---

## ৭. মূল কারণের র‌্যাঙ্কিং

| র‌্যাঙ্ক | কারণ | Cold-এ প্রভাব | এই warm লগে প্রভাব |
| --- | --- | --- | --- |
| ১ | App Dockerfile-এ PHP extension source থেকে compile | ১২–২৫ মিনিট | ০ (cached) |
| ২ | টেকসই remote/CI build cache নেই; হোস্ট prune → cold rebuild | ১২ মিনিটকে ৪৫–৬০ মিনিটে তোলে | হিস্টরিক্যাল তারতম্য ব্যাখ্যা করে |
| ৩ | Coolify ডিস্কে বিশাল ফাইনাল image export/unpack | ৩–৬+ মিনিট | **~৩.৬ মিনিট export + ৭৪স কপি** |
| ৪ | প্রতি কোড চেঞ্জে React Compiler Babel + বড় Vite graph | ৩–৮+ মিনিট | **~৩.৫ মিনিট (#19)** |
| ৫ | Cache miss-এ production apt + PGDG client | ৪–১০ মিনিট | ০ (cached) |
| ৬ | `COPY --from=node /usr/local/` মেগা-লেয়ার | Uncached হলে ৩–৮ মিনিট | ০ (cached) |
| ৭ | প্রতি বিল্ডে PWA splash generation | ~৩০–৬০স | ~৪০স |
| ৮ | Secret build arg হিসেবে inject | Cache bust + সিকিউরিটি ঝুঁকি | ৩৮টি ওয়ার্নিং |
| — | docker-compose vs Dockerfile pack | নগণ্য | Coolify ইতিমধ্যে rollout-এ compose ব্যবহার করে |

---

## ৮. docker-compose যা *ঠিক করবে না*

Coolify ইতিমধ্যে চালিয়েছে:

```text
docker compose … up --build -d
```

rolling update-এর জন্য। প্রজেক্টকে প্রাইমারি build definition হিসেবে “docker-compose-এ নিয়ে যাওয়া” **করে না**:

- PHP extension compilation স্কিপ
- Vite/Babel খরচ কমানো
- কয়েকশ MB layer export এড়ানো
- prune-এর পরও টেকসই cache দেওয়া

Compose হলো orchestration। স্পিড আসে **image strategy** থেকে।

---

## ৯. সুপারিশকৃত সমাধান (সেরা / সিকিউর / অ্যাডভান্সড)

### Phase A — তাৎক্ষণিক (কনফিগ + ছোট Dockerfile পরিচ্ছন্নতা)

1. Coolify: **runtime secret build arg হিসেবে inject বন্ধ**; শুধু `VITE_*` পাবলিক arg allowlist।
2. Production image থেকে `nano` সরান।
3. Production শুধু Postgres হলে `pdo_mysql` বাদ দিন (cold build-এ কম compile)।
4. জেনারেট করা PWA icon/splash কমিট করুন (বা generator শুধু প্রয়োজনীয় সাইজে সীমিত করুন)—বিল্ডে ~৪০স বাঁচে + `/var/www`-এ কম ফাইল।
5. পুরো `/var/www` ট্রিতে `COPY --chown` এড়ান; আগে কপি, পরে শুধু `storage` + `bootstrap/cache`-এ `chown` (প্রায়ই দ্রুত, কম dirty metadata)।

**প্রত্যাশা:** কম আকস্মিক cold build; warm path হয়তো −১–২ মিনিট।

### Phase B — উচ্চ ROI Dockerfile আর্কিটেকচার

1. একটি **prebuilt base image** পাবলিশ করুন (যেমন `ghcr.io/<org>/puffmemate-php:8.4-exts`)—যাতে আগে থেকেই PHP 8.4-FPM + সব extension + redis + runtime libs + nginx/supervisor/postgresql-client-18 থাকে।
2. App `Dockerfile` হয়ে যাবে:
   - `FROM` base (কোনো compile নয়)
   - BuildKit cache mount সহ Composer stage
   - **আলাদা** `node:24` frontend stage (Node PHP-তে কপি করবেন **না**)
   - Slim ফাইনাল stage: শুধু app ফাইল + `public/build`
3. BuildKit cache যোগ করুন:

```dockerfile
RUN --mount=type=cache,target=/root/.composer/cache composer install …
RUN --mount=type=cache,target=/root/.local/share/pnpm/store pnpm install …
```

**প্রত্যাশিত warm কোড ডিপ্লয়:** ~৬–১০ মিনিট → টার্গেট **~৪–৭ মিনিট**।  
**প্রত্যাশিত cold (base ইতিমধ্যে pulled):** ৪৫–৬০-এর বদলে ~১০–১৮ মিনিট।

### Phase C — গোল্ড পাথ (অ্যাডভান্সড)

1. GitHub Actions (বা build হোস্টে Coolify CI) immutable ট্যাগ GHCR-এ build ও push করে।
2. Coolify Application **prebuilt image** ডিপ্লয় করে (সার্ভারে কোনো compile নয়)।
3. Rolling update হয়ে যায়: pull + healthcheck + swap।

**প্রত্যাশিত ডিপ্লয় সময়:** **~২–৫ মিনিট**।

### Phase D — Frontend পারফরম্যান্স (সমান্তরাল)

1. প্রোড কোয়ালিটির জন্য React Compiler রাখুন, কিন্তু সম্ভব হলে শুধু পরিবর্তিত ফাইলে compile / Vite prebundle-এর CI cache মূল্যায়ন করুন।
2. `fcm.ts`-এর ineffective dynamic import ঠিক করুন।
3. `app` / editor / reports / leaflet / icon-catalog আরও আগ্রাসীভাবে ভাগ করুন (`import()`), ৫০০KB+ chunk কমান।
4. Type CI থেকে কমিট থাকলে Docker-এর ভিতরে Wayfinder generation না চালানোর কথা ভাবুন।

---

## ১০. টার্গেট SLO

| সিনারিও | বর্তমান (পর্যবেক্ষিত / অনুমিত) | Phase B-এর পর টার্গেট | Phase C-এর পর টার্গেট |
| --- | --- | --- | --- |
| Warm শুধু-কোড ডিপ্লয় | ~১২ মিনিট (এই লগ) | ৪–৭ মিনিট | ২–৫ মিনিট (শুধু pull) |
| Cold পুরো rebuild | ৩৫–৬০+ মিনিট | ১০–১৮ মিনিট | বিরল (শুধু base rebuild) |
| `Dockerfile.base` পরিবর্তন | প্রযোজ্য নয় | একবার ১৫–৩০ মিনিট | একই, বিরল |

---

## ১১. অ্যাপেন্ডিক্স — মূল লগ অংশ (স্যানিটাইজড)

### ব্যয়বহুল compile layer-এ cache hit

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

### Secret injection ওয়ার্নিং (নমুনা)

```text
38 warnings found
SecretsUsedInArgOrEnv: … ARG "APP_KEY"
SecretsUsedInArgOrEnv: … ARG "DB_PASSWORD"
SecretsUsedInArgOrEnv: … ARG "REDIS_PASSWORD"
SecretsUsedInArgOrEnv: … ARG "AWS_SECRET_ACCESS_KEY"
SecretsUsedInArgOrEnv: … ARG "REVERB_APP_SECRET"
```

---

## ১২. পরবর্তী ইমপ্লিমেন্টেশন ধাপ

সুপারিশকৃত ক্রম:

1. Coolify build-arg allowlist ঠিক করুন (সিকিউরিটি + cache স্থিতিশীলতা) — একই দিনে।
2. `Dockerfile.base` + slim multi-stage app Dockerfile ইমপ্লিমেন্ট করুন — ডিপ্লয়-টাইমে সর্বোচ্চ ROI।
3. ইচ্ছামতো registry-ভিত্তিক Coolify ডিপ্লয়ে যান—২–৫ মিনিট রিলিজের জন্য।

এই রিপোর্ট শুধু অ্যানালাইসিস; লেখার সময় কোনো প্রোডাকশন Dockerfile পরিবর্তন করা হয়নি।
