<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\WholesaleInquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreWholesaleInquiryRequest;
use App\Models\WholesaleInquiry;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;

class WholesaleInquiryController extends Controller
{
    public function store(StoreWholesaleInquiryRequest $request): RedirectResponse
    {
        WholesaleInquiry::query()->create([
            ...$request->validated(),
            'status' => WholesaleInquiryStatus::New,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Toast::success('Thanks — your wholesale inquiry was sent.');

        return back();
    }
}
