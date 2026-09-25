<?php

namespace App\Enums;

enum FulfillmentStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Completed = 'completed';
}
