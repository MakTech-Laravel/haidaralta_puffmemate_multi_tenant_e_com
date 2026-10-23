<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchArea>
 */
class BranchAreaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(-34.0, -33.5),
            'longitude' => fake()->longitude(150.5, 151.5),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->optional()->postcode(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (BranchArea $area): void {
            if ($area->branches()->exists()) {
                return;
            }

            $area->branches()->attach(Branch::factory()->create()->id);
        });
    }

    public function forBranch(Branch|int $branch): static
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        return $this->afterCreating(function (BranchArea $area) use ($branchId): void {
            $area->branches()->sync([$branchId]);
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
