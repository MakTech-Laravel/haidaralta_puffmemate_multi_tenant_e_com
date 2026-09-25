<?php

namespace App\Enums;

enum CustomerAddressType: string
{
    case Shipping = 'shipping';
    case Billing = 'billing';
}
