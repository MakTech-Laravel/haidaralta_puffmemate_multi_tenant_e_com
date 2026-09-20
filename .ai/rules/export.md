---
paths:
    - 'resources/js/components/export/**'
---

# Export

## Portal-agnostic ExportDropdown uses full GET downloads

ExportDropdown is portal-agnostic: pass a Wayfinder export URL and DataTable query (filter/sort/page/per_page plus product/branch/locked). Nested CSV and Excel menus always offer scopes all|period|filters|page. Scope hrefs must be real <a> links so the browser downloads the file; never router.get, useForm, or an Inertia visit (binary XLSX would be parsed as a page). Do not add export actions to list-row menus. Roles/permissions CSV streamDownload buttons stay as-is.
