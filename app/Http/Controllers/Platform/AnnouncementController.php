<?php

namespace App\Http\Controllers\Platform;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementSendMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\AnnouncementRecipientSearchRequest;
use App\Http\Requests\Platform\StoreAnnouncementRequest;
use App\Models\User;
use App\Services\Announcements\AnnouncementService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('platform/announcements/index');
    }

    public function store(
        StoreAnnouncementRequest $request,
        AnnouncementService $announcements,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        $audiences = $request->audiences();

        if ($request->sendMode() === AnnouncementSendMode::Individual) {
            $unique = [];

            foreach ($request->recipients() as $person) {
                $unique[$person['audience']->value] = $person['audience'];
            }

            $audiences = array_values($unique);
        }

        $announcements->send(
            actor: $actor,
            mode: $request->sendMode(),
            audiences: $audiences,
            people: $request->recipients(),
            type: $request->type(),
            icon: (string) $request->validated('icon'),
            title: (string) $request->validated('title'),
            message: (string) $request->validated('message'),
            options: [
                'link_label' => $request->validated('link_label'),
                'link_url' => $request->validated('link_url'),
                'via_phone' => $request->boolean('via_phone'),
                'via_email' => $request->boolean('via_email'),
                'via_push' => $request->boolean('via_push'),
            ],
        );

        Toast::success('Notification sent.');

        return redirect()->route('platform.announcements.index');
    }

    public function recipients(AnnouncementRecipientSearchRequest $request): JsonResponse
    {
        $audience = AnnouncementAudience::from((string) $request->validated('audience'));
        $term = trim((string) $request->validated('q', ''));

        $people = User::query()
            ->where('is_active', true)
            ->where('user_type', $audience->userType())
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($search) use ($term): void {
                    $search->where('name', 'like', '%'.$term.'%')
                        ->orWhere('phone', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'phone'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
            ])
            ->all();

        return response()->json(['people' => $people]);
    }
}
