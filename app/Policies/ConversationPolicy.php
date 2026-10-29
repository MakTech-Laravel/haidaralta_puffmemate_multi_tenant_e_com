<?php

namespace App\Policies;

use App\Enums\ConversationType;
use App\Enums\PermissionEnum;
use App\Models\Conversation;
use App\Models\User;

/**
 * Access to order conversations, branch↔driver ops, and platform↔branch chats.
 */
class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isPlatform() || $user->isBranch()) {
            return $this->hasOperatorPermission($user)
                || $this->hasDriverOpsPermission($user)
                || $this->hasPlatformBranchPermission($user);
        }

        return $user->isCustomer() || $user->isDriver();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if ($conversation->isDriverOps()) {
            return $this->canAccessDriverOps($user, $conversation);
        }

        if ($conversation->isPlatformBranch()) {
            return $this->canAccessPlatformBranch($user, $conversation);
        }

        if ($user->isPlatform() || $user->isBranch()) {
            return $this->isOrderOperatorFor($user, $conversation);
        }

        return $this->isParticipant($user, $conversation);
    }

    /**
     * Opening order threads is operators-only; other types use dedicated create* abilities.
     */
    public function create(User $user): bool
    {
        return ($user->isPlatform() || $user->isBranch())
            && $this->hasOperatorPermission($user);
    }

    public function createDriverOps(User $user): bool
    {
        if ($user->isDriver()) {
            return true;
        }

        return $user->isBranch() && $this->hasDriverOpsPermission($user);
    }

    public function createPlatformBranch(User $user): bool
    {
        return $this->hasPlatformBranchPermission($user);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->isOpen() && $this->view($user, $conversation);
    }

    public function close(User $user, Conversation $conversation): bool
    {
        if ($conversation->isDriverOps()) {
            return $user->isBranch()
                && $user->branch_id === $conversation->branch_id
                && $this->hasDriverOpsPermission($user);
        }

        if ($conversation->isPlatformBranch()) {
            return $this->canAccessPlatformBranch($user, $conversation);
        }

        return $this->isOrderOperatorFor($user, $conversation);
    }

    public function addParticipant(User $user, Conversation $conversation): bool
    {
        return $conversation->isOpen() && $this->view($user, $conversation)
            && ($user->isPlatform() || $user->isBranch());
    }

    private function canAccessDriverOps(User $user, Conversation $conversation): bool
    {
        if ($user->isDriver()) {
            return $conversation->driver_id === $user->id;
        }

        if ($user->isBranch()) {
            return $user->branch_id === $conversation->branch_id
                && $this->hasDriverOpsPermission($user);
        }

        return false;
    }

    private function canAccessPlatformBranch(User $user, Conversation $conversation): bool
    {
        if ($user->isPlatform()) {
            return $user->can(PermissionEnum::MANAGE_PLATFORM_BRANCH_CHAT->value);
        }

        if ($user->isBranch()) {
            return $user->branch_id === $conversation->branch_id
                && $user->can(PermissionEnum::MANAGE_BRANCH_PLATFORM_CHAT->value);
        }

        return false;
    }

    private function isOrderOperatorFor(User $user, Conversation $conversation): bool
    {
        if ($conversation->type !== ConversationType::Order) {
            return false;
        }

        if ($user->isPlatform()) {
            return $user->can(PermissionEnum::MANAGE_CONVERSATIONS->value);
        }

        if ($user->isBranch()) {
            return $user->branch_id === $conversation->branch_id
                && $user->can(PermissionEnum::MANAGE_BRANCH_CONVERSATIONS->value);
        }

        return false;
    }

    private function hasOperatorPermission(User $user): bool
    {
        return $user->isPlatform()
            ? $user->can(PermissionEnum::MANAGE_CONVERSATIONS->value)
            : $user->can(PermissionEnum::MANAGE_BRANCH_CONVERSATIONS->value);
    }

    private function hasDriverOpsPermission(User $user): bool
    {
        return $user->isBranch()
            && $user->can(PermissionEnum::MANAGE_BRANCH_DRIVER_CHAT->value);
    }

    private function hasPlatformBranchPermission(User $user): bool
    {
        if ($user->isPlatform()) {
            return $user->can(PermissionEnum::MANAGE_PLATFORM_BRANCH_CHAT->value);
        }

        if ($user->isBranch()) {
            return $user->can(PermissionEnum::MANAGE_BRANCH_PLATFORM_CHAT->value);
        }

        return false;
    }

    private function isParticipant(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }
}
