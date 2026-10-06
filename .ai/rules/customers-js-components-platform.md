---
paths:
  - 'app/Http/Controllers/Platform/CustomerController.php,app/Services/Platform/PlatformCustomerService.php,app/DataTables/PlatformCustomerDataTable.php,resources/js/pages/platform/customers/**,resources/js/components/platform/nav.ts'
---

# Customers Js Components Platform

## Platform Customers show edit Status service
Platform Customers under manage-users use PlatformCustomerService for create/update. List has Status (is_active) column+filter plus Registration, Phone, Address. Show/edit/update exist; disable = set is_active false on edit (no destroy). Create/update redirect to show.

## Customers list Edit Activate actions
Customers list Actions: Details, Edit, and Activate/Deactivate. Deactivate/Activate posts to platform.customers.toggle-status which flips is_active via PlatformCustomerService::setActive (confirm dialog on the index).
