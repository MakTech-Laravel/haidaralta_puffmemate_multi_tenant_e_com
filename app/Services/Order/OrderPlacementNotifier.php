<?php

namespace App\Services\Order;

use App\Enums\NotificationType;
use App\Enums\OtpPurpose;
use App\Mail\OrderPlacementCodeMail;
use App\Models\Order;
use App\Services\Notifications\NotificationService;
use App\Services\OtpService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OrderPlacementNotifier
{
    public const TTL_MINUTES = 60 * 24;

    public function __construct(
        private OtpService $otp,
        private NotificationService $notifications,
    ) {}

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

        [, $code] = $this->otp->issueCode($phone, OtpPurpose::OrderPlaced, $order, self::TTL_MINUTES);

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
            'Your code for order '.$order->order_number.' is '.$code.'. It expires in 24 hours.',
            ['order_number' => $order->order_number, 'code' => $code],
        );
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
