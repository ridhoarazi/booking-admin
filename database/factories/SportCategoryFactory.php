<?php

namespace Database\Factories;

use App\Models\SportCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SportCategory>
 */
class SportCategoryFactory extends Factory
{
    protected $model = SportCategory::class;

    public function definition(): array
    {
        $name = 'Cabang '.fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => 'sports',
            'is_active' => true,
        ];
    }
}
