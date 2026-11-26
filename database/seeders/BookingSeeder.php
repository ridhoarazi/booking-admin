<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\Field;
use App\Models\FieldSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user@booking.test')->first();

        if (! $user) {
            return;
        }

        $fields = Field::where('is_active', true)->orderBy('id')->take(2)->get();

        foreach ($fields as $index => $field) {
            $slot = FieldSlot::query()
                ->whereHas('schedule', fn ($query) => $query
                    ->where('field_id', $field->id)
                    ->where('date', '>', now()->toDateString()))
                ->where('status', SlotStatus::Available->value)
                ->orderBy('field_schedule_id')
                ->orderBy('start_time')
                ->first();

            if (! $slot) {
                continue;
            }

            DB::transaction(function () use ($user, $field, $slot, $index) {
                $status = $index === 0
                    ? BookingStatus::Pending
                    : BookingStatus::Confirmed;

                Booking::firstOrCreate(
                    ['booking_code' => 'BK-DEMO-'.$field->id],
                    [
                        'user_id' => $user->id,
                        'field_id' => $field->id,
                        'field_slot_id' => $slot->id,
                        'total_price' => $field->price_per_hour,
                        'status' => $status,
                        'notes' => 'Data demo seeder',
                    ],
                );

                $slot->update(['status' => SlotStatus::Booked]);
            });
        }
    }
}
