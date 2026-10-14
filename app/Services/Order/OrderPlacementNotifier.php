<?php

namespace App\Services\Order;

use App\Enums\NotificationType;
use App\Enums\OtpPurpose;
use App\Mail\OrderPlacementCodeMail;
use App\Models\Order;
use App\Services\Notifications\NotificationService;
use App\Services\OtpService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OrderPlacementNotifier
{
    public const RESEND_LIMIT = 3;

    public const RESEND_WINDOW_SECONDS = 600;

    public function __construct(
        private OtpService $otp,
        private NotificationService $notifications,
    ) {}

    /**
     * Send the delivery code. It stays valid until the order is delivered,
     * cancelled, or a new code replaces it.
     */
    public function issue(Order $order): void
    {
        $order->loadMissing('customer');

        if (! $order->status->allowsPlacementCode()) {
            throw ValidationException::withMessages([
                'code' => 'A delivery code is not available for this order.',
            ]);
        }

        $phone = $this->phone($order);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'code' => 'This order has no phone number for a delivery code.',
            ]);
        }

        [, $code] = $this->otp->issueCode($phone, OtpPurpose::OrderPlaced, $order, ttlMinutes: null, storeReadable: true);

        $customer = $order->customer;
        $email = $customer?->email;

        if (is_string($email) && $email !== '') {
            Mail::to($email)->send(new OrderPlacementCodeMail(
                $order,
                $code,
                $customer->name ?? 'Customer',
            ));
        }

        $this->notifications->send(
            [$order->customer_id],
            NotificationType::Order,
            'Delivery code',
            'Your code for order '.$order->order_number.' is '.$code.'. It stays valid until your order is delivered.',
            ['order_number' => $order->order_number, 'code' => $code],
        );
    }

    /**
     * Send a replacement code. Every portal shares one limit per order so the
     * customer phone cannot be flooded.
     */
    public function resend(Order $order): void
    {
        $key = 'delivery-code:'.$order->id;

        if (RateLimiter::tooManyAttempts($key, self::RESEND_LIMIT)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            throw ValidationException::withMessages([
                'code' => 'Too many codes sent. Try again in '.$minutes.' '.str('minute')->plural($minutes).'.',
            ]);
        }

        $this->issue($order);

        RateLimiter::hit($key, self::RESEND_WINDOW_SECONDS);
    }

    /**
     * The code the customer can still give the driver, or null when it has
     * been used, locked, or was never stored readable.
     */
    public function currentCode(Order $order): ?string
    {
        $phone = $this->phone($order);

        if ($phone === null) {
            return null;
        }

        try {
            return $this->otp->active($phone, OtpPurpose::OrderPlaced, $order)?->code_encrypted;
        } catch (DecryptException) {
            return null;
        }
    }

    public function discard(Order $order): void
    {
        $phone = $this->phone($order);

        if ($phone !== null) {
            $this->otp->discard($phone, OtpPurpose::OrderPlaced, $order);
        }
    }

    public function phone(Order $order): ?string
    {
        $snapshot = $order->shipping_address_snapshot['phone'] ?? null;

        if (is_string($snapshot) && $snapshot !== '') {
            return $snapshot;
        }

        $phone = $order->customer?->phone;

        return is_string($phone) && $phone !== '' ? $phone : null;
    }
}
