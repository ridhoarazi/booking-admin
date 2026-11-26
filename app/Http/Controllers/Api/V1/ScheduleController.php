<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\FieldSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request, Field $field): JsonResponse
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $today = now()->toDateString();
        $now = now()->format('H:i:s');
        $date = $filters['date'] ?? null;

        $schedules = FieldSchedule::with(['slots' => fn ($query) => $query->ordered()])
            ->where('field_id', $field->id)
            ->where('is_active', true)
            ->where('date', '>=', $today)
            ->when($date !== null, fn ($query) => $query->whereDate('date', $date))
            ->ordered()
            ->get();

        return response()->json([
            'data' => $schedules->map(function (FieldSchedule $schedule) use ($today, $now) {
                $slots = $schedule->slots;

                if ($schedule->date->toDateString() === $today) {
                    $slots = $slots
                        ->filter(fn ($slot) => $slot->start_time > $now)
                        ->values();
                }

                return [
                    'date' => $schedule->date->toDateString(),
                    'slots' => $slots->map(fn ($slot) => [
                        'id' => $slot->id,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'status' => $slot->status?->value,
                    ])->values(),
                ];
            })->values(),
        ]);
    }
}
