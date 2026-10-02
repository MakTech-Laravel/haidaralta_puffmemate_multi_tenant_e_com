<?php

namespace App\Services\Storefront;

use App\Enums\WholesaleInquiryStatus;
use App\Models\WholesaleInquiry;

class WholesaleInquiryService
{
    /**
     * @param  array{
     *     full_name: string,
     *     phone: string,
     *     company?: string|null,
     *     email?: string|null,
     *     address: string,
     *     latitude: string|numeric,
     *     longitude: string|numeric,
     *     message: string
     * }  $validated
     */
    public function store(array $validated, ?string $ipAddress, ?string $userAgent): WholesaleInquiry
    {
        return WholesaleInquiry::query()->create([
            ...$validated,
            'status' => WholesaleInquiryStatus::New,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function markRead(WholesaleInquiry $inquiry): void
    {
        if ($inquiry->status === WholesaleInquiryStatus::Read) {
            return;
        }

        $inquiry->forceFill([
            'status' => WholesaleInquiryStatus::Read,
            'read_at' => now(),
        ])->save();
    }
}
