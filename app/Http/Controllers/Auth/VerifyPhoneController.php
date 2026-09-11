<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyPhoneRequest;
use App\Services\OtpService;
use App\Support\PortalAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerifyPhoneController extends Controller
{
    /**
     * Show the phone verification prompt.
     */
    public function notice(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && $user->phone_verified_at !== null) {
            return redirect()->intended(PortalAuth::dashboardPath($user));
        }

        return Inertia::render('auth/verify-phone', [
            'phone' => $user?->phone,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Send (or resend) a phone verification OTP.
     */
    public function send(Request $request, OtpService $otpService): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        if ($user->phone_verified_at !== null) {
            return redirect()->intended(PortalAuth::dashboardPath($user));
        }

        $otpService->issue($user->phone, OtpPurpose::PhoneVerify);

        return back()->with('status', __('A verification code was sent to your phone.'));
    }

    /**
     * Verify the phone OTP.
     */
    public function verify(VerifyPhoneRequest $request, OtpService $otpService): RedirectResponse
    {
        $request->verify($otpService);

        return redirect()
            ->intended(PortalAuth::dashboardPath($request->user()))
            ->with('status', __('Your phone number has been verified.'));
    }
}
