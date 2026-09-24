<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use App\Support\MediaUrl;
use App\Support\PublicDiskMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User && $user->user_type === UserType::DRIVER) {
            return redirect()->route('driver.profile.edit');
        }

        return Inertia::render('settings/profile', [
            'mustVerifyPhone' => $user?->phone_verified_at === null,
            'status' => $request->session()->get('status'),
            'profile' => $user instanceof User ? [
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'avatar_url' => MediaUrl::resolve($user->avatar, fallback: null),
            ] : null,
        ]);
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

        $user->avatar = PublicDiskMedia::replace(
            $user->avatar,
            $request->file('avatar'),
            PublicDiskMedia::USER_AVATARS,
            $request->boolean('remove_avatar'),
        );

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return back();
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();
        $guard = Auth::getDefaultDriver();

        Auth::guard($guard)->logout();

        if ($user instanceof User) {
            PublicDiskMedia::delete($user->avatar);
            $user->delete();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
