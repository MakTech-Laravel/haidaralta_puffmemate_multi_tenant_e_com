---
paths:
  - 'app/DataTables/OrderDataTable.php,resources/js/pages/platform/orders/index.tsx'
---

# Platform Orders

## Platform orders table uses enum labels
OrderDataTable rows include status_label and payment_status_label from OrderStatus/PaymentStatus::label(). The platform orders index badges must render those labels, never the raw enum value (e.g. Pending collection, not pending_collection / Pending_collection).
