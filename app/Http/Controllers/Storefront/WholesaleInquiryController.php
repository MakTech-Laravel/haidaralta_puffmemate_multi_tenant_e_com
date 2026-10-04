<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreWholesaleInquiryRequest;
use App\Services\Storefront\WholesaleInquiryService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;

class WholesaleInquiryController extends Controller
{
    public function store(
        StoreWholesaleInquiryRequest $request,
        WholesaleInquiryService $wholesaleInquiries,
    ): RedirectResponse {
        $wholesaleInquiries->store(
            $request->validated(),
            $request->ip(),
            $request->userAgent(),
        );

        Toast::success('Thanks — your wholesale inquiry was sent.');

        return back();
    }
}
