<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Field;
use App\Models\FieldSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'booking_code' => 'BK-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'field_id' => Field::factory(),
            'field_slot_id' => FieldSlot::factory(),
            'total_price' => fake()->numberBetween(5, 25) * 10000,
            'status' => BookingStatus::Pending,
            'notes' => null,
            'cancelled_at' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Jaga konsistensi data uji: setiap booking punya baris booking_slots.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Booking $booking) {
            if ($booking->field_slot_id === null) {
                return;
            }

            $booking->slots()->firstOrCreate(
                ['field_slot_id' => $booking->field_slot_id],
                ['price' => $booking->total_price]
            );
        });
    }
}
