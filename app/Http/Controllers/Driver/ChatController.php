<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    /**
     * Order-scoped chats. The inbox itself ships in a later pass; this
     * screen exists so the field tab bar has a real destination.
     */
    public function index(): Response
    {
        return Inertia::render('driver/chats/index');
    }
}
