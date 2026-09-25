---
paths:
  - 'resources/js/pages/platform/announcements/**'
---

# Announcements

## Announcement compose is design-only
GET /platform/announcements is a static compose sketch (Group/Individual, multi-select Customer/Driver/Branch/Platform, dummy Individual picker, type/icon/title/description/link, Phone/Email/Push toggle, System locked on). No send, save, list, FCM, mail, or SMS yet.

## Announcement type sits next to full Lucide picker
Message Type + Icon share a compact flex row (Type min-w-40 so Info/Promo/Alert/Update stay readable). Icon uses LucideIconPicker mode=dialog triggerVariant=button with no allowedIcons so the full generated catalog loads. Do not restore the 8-icon popover.

## Who is four comboboxes; Recipients are chips
Who is four columns (Customers, Drivers, Branch, Platform), each a product-category combobox with placeholders Select customers/drivers/staff/platform and no chips in the trigger. Recipients is Individual-only: stacked labels plus removable chips (space-y-8), no second picker. Do not restore audience cards.

## Who cards for Group, comboboxes for Individual
Who follows send mode: Group shows the original Customer/Driver/Branch/Platform ChoiceCards (multi-select). Individual shows the four category-style comboboxes plus Recipients chips. Switching mode keeps both selections. Do not show cards in Individual or comboboxes in Group.
