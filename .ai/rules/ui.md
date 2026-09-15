---
paths:
  - 'resources/js/components/ui/{time-picker,wheel-picker}.tsx'
---

# Ui

## TimePicker is iOS wheels, HH:mm
TimePicker is an iOS-style hour / minute / AM-PM wheel inside a Popover (WheelPicker from @ncdai/react-wheel-picker), not a flat list and not native input type=time. Keep the DatePicker trigger chrome. Value stays HH:mm 24h; the trigger and header pill show 12h. Wheels live-commit on snap and stay open until click-outside. Empty opens a visual 08:00 draft without onChange until a wheel moves. Default stepMinutes is 15 (00/15/30/45); off-step stored minutes snap on first commit. Highlight band uses bg-muted and black text — do not paint it primary red (red is fills only).
