<?php

namespace App\Enums;

enum DatabaseBackupType: string
{
    case Export = 'export';
    case Import = 'import';
}
