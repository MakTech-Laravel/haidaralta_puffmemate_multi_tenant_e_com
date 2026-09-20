<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Field account hub: identity, settings, and sign-out. Profile
     * edits still live on the shared settings routes.
     */
    public function index(): Response
    {
        return Inertia::render('driver/account/index');
    }
}
