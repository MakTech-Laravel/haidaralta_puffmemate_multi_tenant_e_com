---
paths:
  - 'app/Http/Controllers/Driver/**'
  - app/Http/Controllers/Driver/PlaceOrderController.php
---

# Controllers Driver

## Driver controllers pass remaining and completed stops
Driver dashboard and route pass remaining/completed assigned stops from DemoCatalog::driverRemainingOrders()/driverCompletedOrders(). Do not pass currentArea, laterAreas, areaCount, or suburb batches. Admins assign by load: mix places on one driver, or split a busy street across drivers.

## Driver profile uses DriverPayload not an account hub
ProfileController::index at GET /driver/profile uses DriverPayload::detail for the signed-in driver. Redirect /driver/account to /driver/profile. Do not restore AccountController as a field hub.

## Driver profile edit is a field page
Driver self-edit is GET/PATCH /driver/profile/edit via Driver\ProfileController, not settings/profile. Drivers may update name, phone, email, avatar, license_number, address, vehicle_type, and vehicle image. Do not let them change is_active, notes, availability_status, branches, or roster. Redirect GET /settings/profile to driver.profile.edit.

## Driver security password stays on Driver stack
Driver password changes go through Driver\SecurityController::updatePassword, DriverService::updatePassword, and UpdateDriverPasswordRequest — not Fortify user-password.update or Settings. Driver 2FA is Fortify-only (enable/confirm/disable/QR/recovery via @/routes/two-factor). Do not add a Driver 2FA service or table. Login still skips the Fortify 2FA challenge.

## Driver Place Order products use driver_inventories
GET /driver/place-order builds products via DriverPlaceOrderCatalog from the signed-in driver’s driver_inventories. Customers and paymentMethods stay DemoCatalog. No POST/create yet; Place order button is a demo toast until live order create exists.
