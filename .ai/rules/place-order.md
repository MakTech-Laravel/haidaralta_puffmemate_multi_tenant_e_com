---
paths:
  - 'resources/js/components/driver/place-order/**'
---

# Place Order

## Place Order products are search-first with a 3-row viewport
Place Order products are search-first (and optional category chips). Never render the full assigned catalog by default. Show a fixed ~3-row scroll viewport and load more with client-side infinite scroll (PAGE_SIZE 3). Selected qty>0 lines stay in an In this order strip below search/results. Cap is progressive via infinite scroll until a live search API exists.

## Place Order customer is unified search then Add
No Registered/New toggle. Search by name or phone; when there are no matches, show Add. Add prefills phone if the query looks like a phone, otherwise name, then require the remaining name/phone field. Do not show Address or Location on this screen.

## Place Order customer auto-search
Place Order customer lookup is a single debounced search input (name/email/phone) with no Look up button. POST /driver/place-order/customers accepts q (min 2) and returns customers[] via CustomerResolutionService::search. Empty results show Add (name + AU phone). Do not restore phone-only Look up validation.

## Place Order products list-first with filter search
Place Order products default to the driver’s assigned inventory with a ~3-row infinite-scroll viewport. Search and category chips only filter that list (name, SKU, description, category). Do not restore the empty search-first hint that hid the catalog until typing.

## Place Order products are dense single list
Place Order products use one dense divided list (thumb + name/price + inline +/-). Do not render a second In this order product list — selected lines stay in the same list (sorted up, highlighted) and an In order (n) filter chip scopes to qty>0. Sticky footer already shows item count/total.
