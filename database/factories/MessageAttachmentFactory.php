<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageAttachment>
 */
class MessageAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->slug(2).'.pdf';

        return [
            'message_id' => Message::factory(),
            'disk' => 'local',
            'path' => 'conversations/1/'.$name,
            'original_name' => $name,
            'mime' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 512000),
        ];
    }

    public function image(): static
    {
        $name = fake()->slug(2).'.jpg';

        return $this->state(fn (): array => [
            'path' => 'conversations/1/'.$name,
            'original_name' => $name,
            'mime' => 'image/jpeg',
        ]);
    }
}
