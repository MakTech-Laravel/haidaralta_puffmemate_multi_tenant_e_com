<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int|null $branch_id
 */
class Role extends SpatieRole
{
    use AuditsChanges;
}
