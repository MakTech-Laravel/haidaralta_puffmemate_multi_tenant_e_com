<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allow multiple shifts per calendar day
    |--------------------------------------------------------------------------
    |
    | When false, Assign Shift rejects staging a second window on the same
    | duty_on for one person. When true, same-day multi-window coverage is allowed.
    |
    */

    'allow_multiple_per_day' => env('SHIFTS_ALLOW_MULTIPLE_PER_DAY', false),

    /*
    |--------------------------------------------------------------------------
    | Platform Administrator sidebar shifts
    |--------------------------------------------------------------------------
    |
    | When true, platform Administrator dropdown shows Shifts and Assign to
    | shift (platform-owned /platform/shifts). Branch management shifts stay
    | visible either way. Default hide — branch-owned shifts are the primary UI.
    |
    */

    'show_platform_administrator_shifts' => env('SHIFTS_SHOW_PLATFORM_ADMINISTRATOR_SHIFTS', false),

];
