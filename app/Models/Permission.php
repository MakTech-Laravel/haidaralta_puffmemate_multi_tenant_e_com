<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use AuditsChanges;
}
