<?php

namespace App\Enums;

enum CatalogBackgroundMode: string
{
    case Transparent = 'transparent';
    case Solid = 'solid';
    case Gradient = 'gradient';
}
