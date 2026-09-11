<?php

namespace App\Services\Sms;

use App\Contracts\SendsSms;
use Illuminate\Support\Facades\Log;

class LogSmsSender implements SendsSms
{
    public function send(string $toE164, string $message): void
    {
        Log::info('SMS message', [
            'to' => $toE164,
            'message' => $message,
        ]);
    }
}
