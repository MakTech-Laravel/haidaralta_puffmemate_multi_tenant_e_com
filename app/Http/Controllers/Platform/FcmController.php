<?php

namespace App\Http\Controllers\Platform;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fcm\FcmTestRequest;
use App\Models\User;
use App\Services\Fcm\FcmPushPayload;
use App\Services\Fcm\FcmPushService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class FcmController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->where('user_type', UserType::PLATFORM)
            ->withCount('fcmDeviceTokens')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'token_count' => (int) $user->fcm_device_tokens_count,
            ])
            ->values()
            ->all();

        return Inertia::render('platform/fcm/index', [
            'users' => $users,
            'firebaseConfigured' => filled(config('services.firebase.api_key'))
                && filled(config('services.firebase.vapid_key')),
        ]);
    }

    public function test(FcmTestRequest $request, FcmPushService $service): JsonResponse
    {
        $payload = new FcmPushPayload(
            title: $request->string('title')->toString(),
            body: $request->string('body')->toString(),
        );

        $result = $request->string('mode')->toString() === 'user'
            ? $service->sendToUserIds($request->resolvedUserIds(), $payload)
            : $service->sendToTokens($request->resolvedTokens(), $payload);

        return response()->json([
            'status' => $result->failed === 0,
            'message' => sprintf(
                'FCM send finished: %d sent, %d failed, %d invalid tokens removed.',
                $result->sent,
                $result->failed,
                $result->invalidTokensRemoved,
            ),
            'data' => $result->toArray(),
        ]);
    }
}
