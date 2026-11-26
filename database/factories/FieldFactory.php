<?php

namespace Database\Factories;

use App\Models\Field;
use App\Models\SportCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Field>
 */
class FieldFactory extends Factory
{
    protected $model = Field::class;

    public function definition(): array
    {
        $name = 'Lapangan '.fake()->unique()->word();

        return [
            'sport_category_id' => SportCategory::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'location' => fake()->address(),
            'description' => fake()->sentence(12),
            'price_per_hour' => fake()->numberBetween(5, 25) * 10000,
            'image_path' => null,
            'is_active' => true,
        ];
    }
}
