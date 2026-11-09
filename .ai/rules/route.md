---
paths:
  - 'app/Http/Controllers/Driver/RouteController.php,resources/js/pages/driver/route/**'
---

# Route

## Route highlight comes from orders map pin
RouteController accepts highlight query and passes highlightId. The matching stop is ringed and labelled On map. The route tab is a real map (MapView on the active provider): numbered stops, the driver's own position from the tracker, and an on-demand road route from `GET /driver/route/{order}/directions`. Never call routing automatically on every position update.
