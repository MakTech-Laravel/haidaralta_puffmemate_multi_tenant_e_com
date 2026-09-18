<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Conversation;
use App\Models\User;

/**
 * Operator access to order conversations.
 *
 * Platform staff see every branch; branch staff are locked to their own branch.
 * Customers and drivers reach a thread only as participants — their portals are
 * not built yet, but the rules are enforced now so those screens inherit them.
 */
class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isPlatform() || $user->isBranch()) {
            return $this->hasOperatorPermission($user);
        }

        return $user->isCustomer() || $user->isDriver();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if ($user->isPlatform() || $user->isBranch()) {
            return $this->isOperatorFor($user, $conversation);
        }

        return $this->isParticipant($user, $conversation);
    }

    /**
     * Only operators open a thread for an order; customers and drivers join one.
     */
    public function create(User $user): bool
    {
        return ($user->isPlatform() || $user->isBranch())
            && $this->hasOperatorPermission($user);
    }

    /**
     * A closed thread is read-only for everyone.
     */
    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->isOpen() && $this->view($user, $conversation);
    }

    /**
     * Closing is idempotent: operators may POST close against a thread that
     * is already closed, and the service no-ops instead of 403ing.
     */
    public function close(User $user, Conversation $conversation): bool
    {
        return $this->isOperatorFor($user, $conversation);
    }

    public function addParticipant(User $user, Conversation $conversation): bool
    {
        return $conversation->isOpen() && $this->isOperatorFor($user, $conversation);
    }

    /**
     * Operators manage a thread when they hold the portal permission and, for
     * branch staff, the thread belongs to their own branch.
     */
    private function isOperatorFor(User $user, Conversation $conversation): bool
    {
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

    private function isParticipant(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }
}
