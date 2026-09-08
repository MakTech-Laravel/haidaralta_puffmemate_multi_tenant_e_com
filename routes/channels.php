<?php

use App\Models\User;
use App\Support\PortalAuth;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'App.Models.User.{id}',
    function (User $user, int $id): bool {
        return (int) $user->id === $id;
    },
    ['guards' => [...PortalAuth::PORTAL_GUARDS, 'web']],
);
