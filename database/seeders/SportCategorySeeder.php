<?php

namespace Database\Seeders;

use App\Models\SportCategory;
use Illuminate\Database\Seeder;

class SportCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Futsal', 'slug' => 'futsal', 'icon' => 'sports_soccer'],
            ['name' => 'Badminton', 'slug' => 'badminton', 'icon' => 'sports_tennis'],
            ['name' => 'Basket', 'slug' => 'basket', 'icon' => 'sports_basketball'],
            ['name' => 'Voli', 'slug' => 'voli', 'icon' => 'sports_volleyball'],
        ];

        foreach ($categories as $category) {
            SportCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true],
            );
        }
    }
}
