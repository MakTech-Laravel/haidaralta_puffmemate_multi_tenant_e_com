<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateContactSettingsRequest;
use App\Services\Application\ContactSettingsService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactSettingsController extends Controller
{
    public function __construct(
        private readonly ContactSettingsService $settings,
    ) {}

    public function edit(): Response
    {
        return Inertia::render('platform/settings/contacts', [
            'settings' => $this->settings->formPayload(),
        ]);
    }

    public function update(UpdateContactSettingsRequest $request): RedirectResponse
    {
        $this->settings->update($request->validated(), $request->user()?->id);

        Toast::success('Contact settings saved.');

        return redirect()->route('platform.settings.contacts.edit');
    }
}
