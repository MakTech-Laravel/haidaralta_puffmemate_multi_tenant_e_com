<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\OwnsUserNotificationRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Notifications\InboxService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(private InboxService $inbox) {}

    public function index(): Response
    {
        /** @var User $user */
        $user = request()->user();

        return Inertia::render('driver/notifications/index', [
            'items' => $this->inbox->listFor($user),
            'unreadCount' => $this->inbox->unreadCount($user),
        ]);
    }

    public function show(UserNotification $userNotification): Response
    {
        /** @var User $user */
        $user = request()->user();
        $row = $this->inbox->findFor($user, $userNotification->id);
        $this->inbox->markRead($user, $row);

        return Inertia::render('driver/notifications/show', [
            'notification' => $this->inbox->toItem($row->fresh(['notification'])),
        ]);
    }

    public function read(OwnsUserNotificationRequest $request, UserNotification $userNotification): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->inbox->markRead($user, $userNotification);

        return back();
    }

    public function destroy(OwnsUserNotificationRequest $request, UserNotification $userNotification): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->inbox->delete($user, $userNotification);

        return redirect()->route('driver.notifications.index');
    }
}
