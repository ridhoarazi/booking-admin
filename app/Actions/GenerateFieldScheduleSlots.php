<?php

namespace App\Actions;

use App\Enums\SlotStatus;
use App\Models\FieldSchedule;
use App\Models\FieldSlot;
use Carbon\Carbon;

class GenerateFieldScheduleSlots
{
    public function handle(FieldSchedule $schedule, int $minutesPerSlot = 60): int
    {
        $date = $schedule->date->toDateString();

        $cursor = Carbon::parse($date.' '.$schedule->open_time);
        $end = Carbon::parse($date.' '.$schedule->close_time);
        $created = 0;

        while ($cursor->copy()->addMinutes($minutesPerSlot)->lte($end)) {
            $slot = FieldSlot::firstOrCreate(
                [
                    'field_schedule_id' => $schedule->id,
                    'start_time' => $cursor->format('H:i:s'),
                ],
                [
                    'end_time' => $cursor->copy()->addMinutes($minutesPerSlot)->format('H:i:s'),
                    'status' => SlotStatus::Available,
                ]
            );

            if ($slot->wasRecentlyCreated) {
                $created++;
            }

            $cursor->addMinutes($minutesPerSlot);
        }

        return $created;
    }
}
