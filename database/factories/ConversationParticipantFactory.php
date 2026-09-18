<?php

namespace Database\Factories;

use App\Enums\ConversationParticipantRole;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationParticipant>
 */
class ConversationParticipantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'role' => ConversationParticipantRole::BranchStaff,
            'last_read_message_id' => null,
            'joined_at' => now(),
        ];
    }

    public function role(ConversationParticipantRole $role): static
    {
        return $this->state(fn (): array => ['role' => $role]);
    }
}
