<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\SendSmtpTestEmailRequest;
use App\Http\Requests\Platform\UpdateSmtpSettingsRequest;
use App\Services\Application\SmtpSettingsService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SmtpSettingsController extends Controller
{
    public function __construct(
        private readonly SmtpSettingsService $settings,
    ) {}

    public function edit(): Response
    {
        return Inertia::render('platform/settings/smtp', [
            'settings' => $this->settings->formPayload(),
            'defaultTestEmail' => request()->user()?->email,
        ]);
    }

    public function update(UpdateSmtpSettingsRequest $request): RedirectResponse
    {
        $this->settings->update($request->validated(), $request->user()?->id);

        Toast::success('SMTP settings saved.');

        return redirect()->route('platform.settings.smtp.edit');
    }

    public function test(SendSmtpTestEmailRequest $request): RedirectResponse
    {
        try {
            $this->settings->sendTestEmail($request->validated('email'));
            Toast::success('Test email sent to '.$request->validated('email').'.');
        } catch (Throwable $exception) {
            report($exception);
            Toast::error('Failed to send test email. Check SMTP settings and try again.');
        }

        return redirect()->route('platform.settings.smtp.edit');
    }
}
