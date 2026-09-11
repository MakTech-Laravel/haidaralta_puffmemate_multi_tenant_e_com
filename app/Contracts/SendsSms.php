<?php

namespace App\Contracts;

interface SendsSms
{
    public function send(string $toE164, string $message): void;
}
