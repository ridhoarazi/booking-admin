<?php

namespace Database\Factories;

use App\Enums\SlotStatus;
use App\Models\FieldSchedule;
use App\Models\FieldSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldSlot>
 */
class FieldSlotFactory extends Factory
{
    protected $model = FieldSlot::class;

    public function definition(): array
    {
        return [
            'field_schedule_id' => FieldSchedule::factory(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => SlotStatus::Available,
        ];
    }

    public function booked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SlotStatus::Booked,
        ]);
    }
}
