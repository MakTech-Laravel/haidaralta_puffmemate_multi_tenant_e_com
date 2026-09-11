<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyPasswordOtpRequest;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    /**
     * Show the OTP verification form after requesting a reset code.
     */
    public function createOtp(Request $request): Response|RedirectResponse
    {
        $phone = $request->session()->get('password_reset_otp_phone');

        if (! is_string($phone) || $phone === '') {
            return redirect()->route('password.request');
        }

        return Inertia::render('auth/verify-password-otp', [
            'phone' => $phone,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Verify the password-reset OTP and open the set-password form.
     */
    public function storeOtp(VerifyPasswordOtpRequest $request, OtpService $otpService): RedirectResponse
    {
        $request->verify($otpService);

        $phone = $request->string('phone')->value();

        $request->session()->forget('password_reset_otp_phone');
        $request->session()->put(ResetPasswordRequest::SESSION_KEY, $phone);

        return redirect()->route('password.reset');
    }

    /**
     * Show the set-new-password form after OTP verification.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $phone = $request->session()->get(ResetPasswordRequest::SESSION_KEY);

        if (! is_string($phone) || $phone === '') {
            return redirect()->route('password.request');
        }

        return Inertia::render('auth/reset-password', [
            'phone' => $phone,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Reset the user's password.
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $request->resetPassword();

        return redirect()
            ->route('login')
            ->with('status', __('passwords.reset'));
    }
}
