<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Single active branch
    |--------------------------------------------------------------------------
    |
    | When true, a driver may have only one active branch assignment at a
    | time. Assigning another branch transfers them and keeps the previous
    | row as inactive history. When false, a driver may be active on
    | multiple branches at once.
    |
    */

    'single_active_branch' => env('DRIVER_SINGLE_ACTIVE_BRANCH', true),

];
