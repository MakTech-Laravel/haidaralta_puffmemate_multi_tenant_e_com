---
paths:
  - 'resources/js/components/admin/shift-*.tsx,database/seeders/WorkforceShiftSeeder.php,app/Support/WorkforcePayload.php'
---

# Seeders Support

## Shift board is not Meilisearch
Shift templates, assignments, and day rows are not Searchable. The shift board filters client-side — do not add Scout/Meilisearch to workforce models. WorkforceShiftSeeder is handwritten PuffMeMate roster copy (Floor open, Late trade, Dispatch, Network support, Evening ops) with personal date ranges, no global roster periods, no Faker, and no overnight wrap times.
