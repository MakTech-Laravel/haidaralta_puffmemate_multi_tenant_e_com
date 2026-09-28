<?php

namespace App\Http\Controllers\Customer;

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

        return Inertia::render('customer/notifications/index', [
            'items' => $this->inbox->listFor($user),
        ]);
    }

    public function show(UserNotification $notification): Response
    {
        /** @var User $user */
        $user = request()->user();
        $row = $this->inbox->findFor($user, $notification->id);
        $this->inbox->markRead($user, $row);

        return Inertia::render('customer/notifications/show', [
            'notification' => $this->inbox->toItem($row->fresh(['notification'])),
        ]);
    }

    public function read(OwnsUserNotificationRequest $request, UserNotification $notification): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->inbox->markRead($user, $notification);

        return back();
    }

    public function destroy(OwnsUserNotificationRequest $request, UserNotification $notification): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->inbox->delete($user, $notification);

        return redirect()->route('customer.notifications.index');
    }
}
