<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use AuditsChanges;
}
