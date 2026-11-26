<?php

namespace Database\Factories;

use App\Models\Field;
use App\Models\FieldSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldSchedule>
 */
class FieldScheduleFactory extends Factory
{
    protected $model = FieldSchedule::class;

    public function definition(): array
    {
        return [
            'field_id' => Field::factory(),
            'date' => now()->addDay()->toDateString(),
            'open_time' => '08:00:00',
            'close_time' => '22:00:00',
            'is_active' => true,
        ];
    }
}
