<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case PasswordReset = 'password_reset';
    case PhoneVerify = 'phone_verify';
}
