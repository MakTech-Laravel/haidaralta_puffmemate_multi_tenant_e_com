<?php

namespace App\Services\Storefront;

use App\Enums\ContactInquiryStatus;
use App\Models\ContactInquiry;

class ContactInquiryService
{
    /**
     * @param  array{
     *     full_name: string,
     *     phone: string,
     *     email?: string|null,
     *     type: string,
     *     message: string
     * }  $validated
     */
    public function store(array $validated, ?string $ipAddress, ?string $userAgent): ContactInquiry
    {
        return ContactInquiry::query()->create([
            ...$validated,
            'status' => ContactInquiryStatus::New,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function markRead(ContactInquiry $inquiry): void
    {
        if ($inquiry->status === ContactInquiryStatus::Read) {
            return;
        }

        $inquiry->forceFill([
            'status' => ContactInquiryStatus::Read,
            'read_at' => now(),
        ])->save();
    }
}
