---
paths:
  - 'resources/js/pages/branch/catalog/packs-saves/**'
---

# Packs Saves

## Packs list uses client DataTable dummy rows
Packs & Saves index uses toClientDataTablePayload on dummy packs with showPagination=false. Do not add AbstractDataTable until packs persist. View/Edit row actions stay disabled until show/edit routes exist.
