<?php

namespace App\Enums;

enum ExportScope: string
{
    case All = 'all';
    case Period = 'period';
    case Filters = 'filters';
    case Page = 'page';
}
