<?php

namespace App\Services\Order;

use App\Enums\NotificationType;
use App\Enums\PermissionEnum;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use App\Services\Notifications\PermissionRecipientResolver;

/**
 * Tells a branch's order staff that a new fulfillment needs handling.
 */
class BranchOrderNotifier
{
    public function __construct(
        private NotificationService $notifications,
        private PermissionRecipientResolver $recipients,
    ) {}

    public function newOrder(
        OrderFulfillment $fulfillment,
        string $title,
        string $message,
        User|int|null $createdBy = null,
    ): void {
        $fulfillment->loadMissing(['branch', 'order']);
        $branch = $fulfillment->branch;

        if ($branch === null) {
            return;
        }

        $this->notifications->send(
            $this->recipients->branch($branch->id, PermissionEnum::MANAGE_BRANCH_ORDERS),
            NotificationType::Order,
            $title,
            $message,
            [
                'order_number' => $fulfillment->order?->order_number,
                'fulfillment_id' => $fulfillment->id,
                'link_url' => route('branch.orders.show', [$branch, $fulfillment]),
                'link_label' => 'View order',
            ],
            $createdBy,
        );
    }
}
