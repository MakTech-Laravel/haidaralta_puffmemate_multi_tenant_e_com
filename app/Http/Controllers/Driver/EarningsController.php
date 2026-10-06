<?php

namespace App\Http\Controllers\Driver;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryAttempt;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;

class EarningsController extends Controller
{
    public function index(): Response
    {
        $driverId = auth()->id();

        $payments = Payment::query()
            ->where('collected_by', $driverId)
            ->whereIn('status', [PaymentStatus::Collected, PaymentStatus::Verified])
            ->with('order')
            ->latest('collected_at')
            ->get();

        $total = (float) $payments->sum('amount');
        $cod = (float) $payments->where('gateway', 'cod')->sum('amount');
        $failedAttempts = DeliveryAttempt::query()->where('driver_id', $driverId)->count();

        $entries = $payments->map(fn (Payment $payment): array => [
            'id' => (string) $payment->id,
            'customerName' => $payment->order?->shipping_address_snapshot['name'] ?? 'Customer',
            'orderNumber' => $payment->order?->order_number ?? '',
            'areaLabel' => $payment->order?->shipping_address_snapshot['city'] ?? '',
            'amountLabel' => '$'.number_format((float) $payment->amount, 2),
            'tipLabel' => '$0.00',
            'paymentMethod' => $payment->gateway === 'pos' ? 'paid' : 'cod',
        ])->all();

        return Inertia::render('driver/earnings/index', [
            'earnings' => [
                'todayLabel' => '$'.number_format($total, 2),
                'weekLabel' => '$'.number_format($total, 2),
                'tipsLabel' => '$0.00',
                'perStopLabel' => $payments->isEmpty() ? '$0.00' : '$'.number_format($total / $payments->count(), 2),
                'payoutLabel' => '$'.number_format($total, 2),
                'payoutStatus' => 'Collected',
                'codHeldLabel' => '$'.number_format($cod, 2),
                'dropsToday' => $payments->count(),
                'dropsWeek' => $payments->count(),
                'failedAttempts' => $failedAttempts,
                'days' => $entries === [] ? [] : [[
                    'label' => 'Collections',
                    'totalLabel' => '$'.number_format($total, 2),
                    'entries' => $entries,
                ]],
            ],
        ]);
    }
}
