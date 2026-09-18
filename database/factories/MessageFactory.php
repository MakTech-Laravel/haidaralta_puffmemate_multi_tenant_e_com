<?php

namespace Database\Factories;

use App\Enums\MessageType;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'type' => MessageType::Text,
            'body' => fake()->sentence(),
        ];
    }

    public function system(string $body): static
    {
        return $this->state(fn (): array => [
            'sender_id' => null,
            'type' => MessageType::System,
            'body' => $body,
        ]);
    }

    public function from(User $sender): static
    {
        return $this->state(fn (): array => ['sender_id' => $sender->id]);
    }
}
