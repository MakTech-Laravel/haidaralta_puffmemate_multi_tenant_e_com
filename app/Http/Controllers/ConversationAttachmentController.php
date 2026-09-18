<?php

namespace App\Http\Controllers;

use App\Models\MessageAttachment;
use App\Models\User;
use App\Support\PortalAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves chat attachments from the private disk.
 *
 * Files never live on the public disk, so every download is authorized against
 * the owning conversation first.
 */
class ConversationAttachmentController extends Controller
{
    public function show(MessageAttachment $attachment): StreamedResponse
    {
        $conversation = $attachment->message->conversation;

        Gate::forUser($this->portalUser())->authorize('view', $conversation);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name);
    }

    private function portalUser(): User
    {
        foreach (PortalAuth::PORTAL_GUARDS as $guard) {
            $user = Auth::guard($guard)->user();

            if ($user instanceof User) {
                return $user;
            }
        }

        abort(403);
    }
}
