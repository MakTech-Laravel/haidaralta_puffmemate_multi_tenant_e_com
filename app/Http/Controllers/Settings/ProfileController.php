<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\User;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private MediaUploadService $media) {}

    /**
     * Show the user's profile settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User && $user->user_type === UserType::DRIVER) {
            return redirect()->route('driver.profile.edit');
        }

        $props = [
            'mustVerifyPhone' => $user?->phone_verified_at === null,
            'status' => $request->session()->get('status'),
            'canDeleteAccount' => $user instanceof User && ! $user->user_type->usesMergedAccountProfile(),
            'profile' => $user instanceof User ? [
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'avatar' => $user->hasMedia('avatar') ? 'avatar' : null,
                'avatar_url' => $user->avatarUrl(),
            ] : null,
        ];

        if ($user instanceof User && $user->user_type->usesMergedAccountProfile()) {
            $props = array_merge($props, SecurityController::pageProps($request));
        }

        return Inertia::render('settings/profile', $props);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        unset($validated['avatar'], $validated['remove_avatar']);

        $user->fill($validated);

        if ($user->isDirty('phone')) {
            $user->phone_verified_at = null;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->media->syncSingle(
            $user,
            'avatar',
            $request->file('avatar'),
            $request->boolean('remove_avatar'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return back();
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user instanceof User && $user->user_type->usesMergedAccountProfile(), 403);

        $guard = Auth::getDefaultDriver();

        Auth::guard($guard)->logout();

        if ($user instanceof User) {
            $user->delete();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
