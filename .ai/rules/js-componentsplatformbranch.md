---
paths:
  - 'app/Http/Controllers/{Platform,Branch}/Reports/**, resources/js/pages/{platform,branch}/reports/**, resources/js/components/{platform,branch}/nav.ts'
---

# Js Componentsplatformbranch

## Order Reports naming (not operations/records)
The order dashboard report is **Order Reports** everywhere: UI title, `reports/order-reports` (+ `/export` on platform), route names `*.reports.order-reports`, controllers `OrderReportController`, service `OrderReportService`, export `OrderReportsExport` (`order-reports-*.csv`), pages `*/reports/order-reports`. Temporary 301s remain from `reports/order-operations` — do not reintroduce Order Operations / Order Records names.
