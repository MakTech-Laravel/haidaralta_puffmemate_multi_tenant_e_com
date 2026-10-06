<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreContactInquiryRequest;
use App\Services\Storefront\ContactInquiryService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;

class ContactInquiryController extends Controller
{
    public function store(
        StoreContactInquiryRequest $request,
        ContactInquiryService $contactInquiries,
    ): RedirectResponse {
        $contactInquiries->store(
            $request->validated(),
            $request->ip(),
            $request->userAgent(),
        );

        Toast::success('Thanks — your message was sent.');

        return back();
    }
}
