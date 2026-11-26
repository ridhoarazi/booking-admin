<?php

namespace Database\Seeders;

use App\Models\Field;
use App\Models\SportCategory;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            [
                'category' => 'futsal',
                'name' => 'Lapangan Futsal A',
                'location' => 'Mall XP, Karawang',
                'description' => 'Lapangan futsal indoor dengan rumput sintetis dan pencahayaan LED.',
                'price_per_hour' => 150000,
            ],
            [
                'category' => 'badminton',
                'name' => 'Lapangan Badminton Indoor',
                'location' => 'Jl. Sudirman, Karawang',
                'description' => 'Dua lapangan badminton indoor dengan lantai vinyl standar BWF.',
                'price_per_hour' => 100000,
            ],
            [
                'category' => 'basket',
                'name' => 'Lapangan Basket Kelapa Gading',
                'location' => 'Jl. Kelapa Gading, Karawang',
                'description' => 'Lapangan basket outdoor dengan ring hidrolik dan tribun penonton.',
                'price_per_hour' => 120000,
            ],
            [
                'category' => 'futsal',
                'name' => 'Lapangan Futsal B',
                'location' => 'Taman Ria Indah, Karawang',
                'description' => 'Lapangan futsal semi-indoor dengan area parkir luas.',
                'price_per_hour' => 140000,
            ],
            [
                'category' => 'badminton',
                'name' => 'Lapangan Badminton Outdoor',
                'location' => 'Pondok Indah, Karawang',
                'description' => 'Lapangan badminton outdoor dengan net baru dan pencahayaan malam.',
                'price_per_hour' => 90000,
            ],
        ];

        foreach ($fields as $data) {
            $category = SportCategory::where('slug', $data['category'])->firstOrFail();

            Field::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($data['name'])],
                [
                    'sport_category_id' => $category->id,
                    'name' => $data['name'],
                    'location' => $data['location'],
                    'description' => $data['description'],
                    'price_per_hour' => $data['price_per_hour'],
                    'is_active' => true,
                ],
            );
        }
    }
}
