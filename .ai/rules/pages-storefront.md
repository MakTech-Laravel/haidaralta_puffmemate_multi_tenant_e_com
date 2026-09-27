---
paths:
  - resources/js/pages/storefront/category.tsx
  - resources/js/pages/storefront/checkout.tsx
---

# Pages Storefront

## Category pages have no branch picker
/shop/{category} toolbar is search + sort only (sort lg:w-56). Do not add StorefrontBranchSelect or branches/branchId props. Search and sort use the same Inertia query keys as shop (search, sort).

## Shipping quote posts lat/long via useHttp transform
useHttp.submit is submit(method, url, options), not submit({ url, method, data }). Quote with transform(() => ({ latitude, longitude })).post(quoteShipping.url()) so the pin is in the JSON body. Spreading quoteShipping.post() and adding data sent an empty body and showed latitude/longitude required.

## React useHttp transform is not chainable
In React, useHttp.transform() returns undefined. Call transform(() => ({ latitude, longitude })) then postQuote(quoteShipping.url()) on the next line. Do not write transform().post().

## React useForm transform is not chainable
Call form.transform(...) then form.post(url) on the next line. form.transform(...).post() throws because transform returns undefined in React.
