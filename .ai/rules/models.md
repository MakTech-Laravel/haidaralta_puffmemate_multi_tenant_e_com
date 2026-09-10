---
paths:
    - 'app/Models/{User,Branch}.php'
---

# Models

## Scout timestamps while timestamps=false

SessionGuard remember-token updates set `$model->timestamps = false` before save. Scout’s ModelObserver then calls `toSearchableArray()` while Eloquent date casting for `created_at`/`updated_at` is off, so those attributes are strings. Always normalize via `App\Support\SearchableTimestamp::from()` (Carbon::parse-safe)—never `$this->created_at?->getTimestamp()` alone.
