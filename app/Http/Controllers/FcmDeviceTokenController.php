<?php

namespace App\Http\Controllers;

use App\Http\Requests\Fcm\FcmRegisterRequest;
use App\Http\Requests\Fcm\FcmUnregisterRequest;
use App\Models\FcmDeviceToken;
use Illuminate\Http\JsonResponse;

class FcmDeviceTokenController extends Controller
{
    public function register(FcmRegisterRequest $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->string('token')->toString();

        $device = FcmDeviceToken::query()->updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'device_name' => $request->input('device_name'),
                'user_agent' => $request->userAgent(),
                'platform' => $request->input('platform', 'web'),
                'last_used_at' => now(),
            ],
        );

        return response()->json([
            'status' => true,
            'message' => 'FCM token registered.',
            'data' => [
                'id' => $device->id,
                'user_id' => $device->user_id,
            ],
        ]);
    }

    public function unregister(FcmUnregisterRequest $request): JsonResponse
    {
        $deleted = FcmDeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $request->string('token')->toString())
            ->delete();

        return response()->json([
            'status' => true,
            'message' => $deleted > 0
                ? 'FCM token unregistered.'
                : 'Token not found for this user.',
            'data' => ['deleted' => $deleted],
        ]);
    }
}
