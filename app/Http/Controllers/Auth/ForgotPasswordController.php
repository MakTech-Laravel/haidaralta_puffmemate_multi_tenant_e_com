<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Services\PhoneOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Request a password-reset OTP (does not reveal whether the phone exists).
     */
    public function store(ForgotPasswordRequest $request, PhoneOtpService $otpService): RedirectResponse
    {
        $request->sendResetOtp($otpService);

        $request->session()->put('password_reset_otp_phone', $request->string('phone')->value());

        return redirect()
            ->route('password.otp')
            ->with('status', __('If that phone number is registered, we sent a verification code.'));
    }
}
