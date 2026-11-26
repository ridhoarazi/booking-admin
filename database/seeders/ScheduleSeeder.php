<?php

namespace Database\Seeders;

use App\Enums\SlotStatus;
use App\Models\Field;
use App\Models\FieldSchedule;
use App\Models\FieldSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $fields = Field::where('is_active', true)->get();

        foreach ($fields as $field) {
            for ($day = 0; $day <= 6; $day++) {
                $date = Carbon::today()->addDays($day);

                $schedule = FieldSchedule::updateOrCreate(
                    [
                        'field_id' => $field->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'open_time' => '08:00:00',
                        'close_time' => '22:00:00',
                        'is_active' => true,
                    ],
                );

                $cursor = Carbon::parse($date->toDateString().' 08:00:00');
                $close = Carbon::parse($date->toDateString().' 22:00:00');

                while ($cursor->copy()->addHour()->lte($close)) {
                    FieldSlot::firstOrCreate(
                        [
                            'field_schedule_id' => $schedule->id,
                            'start_time' => $cursor->format('H:i:s'),
                        ],
                        [
                            'end_time' => $cursor->copy()->addHour()->format('H:i:s'),
                            'status' => SlotStatus::Available,
                        ],
                    );

                    $cursor->addHour();
                }
            }
        }
    }
}
