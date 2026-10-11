<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case PasswordReset = 'password_reset';
    case PhoneVerify = 'phone_verify';
    case DeliveryHandover = 'delivery_handover';
    case OrderPlaced = 'order_placed';
    case AccountClaim = 'account_claim';
}
