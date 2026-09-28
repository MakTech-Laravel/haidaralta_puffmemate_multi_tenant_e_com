<?php

namespace Database\Factories;

use App\Models\Pack;
use App\Models\PackImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackImage>
 */
class PackImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pack_id' => Pack::factory(),
            'path' => 'packs/gallery/sample.jpg',
            'sort_order' => 0,
        ];
    }
}
