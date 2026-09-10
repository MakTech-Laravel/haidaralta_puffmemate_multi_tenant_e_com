<?php

namespace App\Enums;

enum DatabaseBackupType: string
{
    case Run = 'run';
    case Cleanup = 'cleanup';
    case Restore = 'restore';
    case Delete = 'delete';
}
