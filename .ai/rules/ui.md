---
paths:
  - 'resources/js/components/ui/{time-picker,wheel-picker}.tsx'
  - resources/js/components/ui/alert.tsx
---

# Ui

## TimePicker is iOS wheels, HH:mm

TimePicker is an iOS-style hour / minute / AM-PM wheel inside a Popover (WheelPicker from @ncdai/react-wheel-picker), not a flat list and not native input type=time. Keep the DatePicker trigger chrome. Value stays HH:mm 24h; the trigger and header pill show 12h. Wheels live-commit on snap and stay open until click-outside. Empty opens a visual 08:00 draft without onChange until a wheel moves. Default stepMinutes is 15 (00/15/30/45); off-step stored minutes snap on first commit. Highlight band uses bg-muted and black text — do not paint it primary red (red is fills only).

## TimePicker clear stays inside the field

Clear (X) stays inside the TimePicker trigger (absolute, left of the clock icon), matching DatePicker. Do not put the clear control beside a w-full trigger.

## Destructive Alert uses red text not white
--destructive-foreground is white for red-fill buttons. Alert variant=destructive must use text-destructive on a light tint (bg-destructive/10), never text-destructive-foreground, or checkout and other banners render as an empty white box.
