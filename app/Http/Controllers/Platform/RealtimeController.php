<?php

namespace App\Http\Controllers\Platform;

use App\Events\RealtimePrivateTestEvent;
use App\Events\ReverbPingEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RealtimeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('platform/realtime/index');
    }

    /**
     * Dispatch a public diagnostic broadcast on the "reverb-ping" channel.
     */
    public function ping(Request $request): JsonResponse
    {
        $event = new ReverbPingEvent(
            message: (string) $request->query('message', 'Hello from Laravel Reverb'),
            triggeredAt: now()->toIso8601String(),
            source: 'platform-console',
        );

        broadcast($event);

        return response()->json([
            'status' => true,
            'message' => 'Event dispatched.',
            'data' => [
                'channel' => 'reverb-ping',
                'event' => 'ping',
                'payload' => $event->broadcastWith(),
            ],
        ]);
    }

    /**
     * Expose the effective broadcasting target (no secrets) for deployment checks.
     */
    public function health(): JsonResponse
    {
        $connection = (string) config('broadcasting.default');
        $options = (array) config("broadcasting.connections.{$connection}.options", []);
        $key = (string) config("broadcasting.connections.{$connection}.key", '');

        return response()->json([
            'status' => true,
            'message' => 'Broadcasting configuration.',
            'data' => [
                'broadcast_connection' => $connection,
                'host' => $options['host'] ?? null,
                'port' => $options['port'] ?? null,
                'scheme' => $options['scheme'] ?? null,
                'app_key_preview' => $key === '' ? null : substr($key, 0, 8).'...',
            ],
        ]);
    }

    /**
     * Send a private diagnostic event to the current platform user.
     */
    public function testNotification(Request $request): JsonResponse
    {
        $user = $request->user();

        $event = new RealtimePrivateTestEvent(
            user: $user,
            message: 'Private channel push sent at '.now()->toIso8601String(),
            triggeredAt: now()->toIso8601String(),
        );

        broadcast($event);

        return response()->json([
            'status' => true,
            'message' => 'Private test event dispatched.',
            'data' => [
                'channel' => 'App.Models.User.'.$user->id,
                'event' => 'realtime.test',
                'payload' => $event->broadcastWith(),
            ],
        ]);
    }
}
