---
paths:
  - 'resources/js/components/driver/place-order/**'
---

# Place Order

## Place Order products are search-first with a 3-row viewport
Place Order products are search-first (and optional category chips). Never render the full assigned catalog by default. Show a fixed ~3-row scroll viewport and load more with client-side infinite scroll (PAGE_SIZE 3). Selected qty>0 lines stay in an In this order strip below search/results. Cap is progressive via infinite scroll until a live search API exists.

## Place Order customer is unified search then Add
No Registered/New toggle. Search by name or phone; when there are no matches, show Add. Add prefills phone if the query looks like a phone, otherwise name, then require the remaining name/phone field. Do not show Address or Location on this screen.
