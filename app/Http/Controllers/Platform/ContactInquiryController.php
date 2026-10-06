<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\ContactInquiryDataTable;
use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use App\Services\Storefront\ContactInquiryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactInquiryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('platform/contact-inquiries/index', [
            'table' => ContactInquiryDataTable::make($request),
        ]);
    }

    public function show(
        ContactInquiry $contactInquiry,
        ContactInquiryService $contactInquiries,
    ): Response {
        $contactInquiries->markRead($contactInquiry);

        return Inertia::render('platform/contact-inquiries/show', [
            'inquiry' => $this->inquiryPayload($contactInquiry),
        ]);
    }

    /**
     * @return array{
     *     id: int,
     *     full_name: string,
     *     phone: string,
     *     email: ?string,
     *     type: string,
     *     type_label: string,
     *     message: string,
     *     status: string,
     *     status_label: string,
     *     read_at: ?string,
     *     ip_address: ?string,
     *     user_agent: ?string,
     *     created_at: ?string
     * }
     */
    private function inquiryPayload(ContactInquiry $inquiry): array
    {
        return [
            'id' => $inquiry->id,
            'full_name' => $inquiry->full_name,
            'phone' => $inquiry->phone,
            'email' => $inquiry->email,
            'type' => $inquiry->type->value,
            'type_label' => $inquiry->type->label(),
            'message' => $inquiry->message,
            'status' => $inquiry->status->value,
            'status_label' => $inquiry->status->label(),
            'read_at' => $inquiry->read_at?->toIso8601String(),
            'ip_address' => $inquiry->ip_address,
            'user_agent' => $inquiry->user_agent,
            'created_at' => $inquiry->created_at?->toIso8601String(),
        ];
    }
}
