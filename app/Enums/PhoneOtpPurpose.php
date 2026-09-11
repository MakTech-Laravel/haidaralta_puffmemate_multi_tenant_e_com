<?php

namespace App\Enums;

enum PhoneOtpPurpose: string
{
    case PasswordReset = 'password_reset';
    case PhoneVerify = 'phone_verify';
}
