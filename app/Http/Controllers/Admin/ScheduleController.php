<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateFieldScheduleSlots;
use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleRequest;
use App\Models\Field;
use App\Models\FieldSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Field $field): View
    {
        $schedules = $field->schedules()
            ->withCount('slots')
            ->orderByDesc('date')
            ->paginate(14);

        return view('admin.schedules.index', compact('field', 'schedules'));
    }

    public function create(Field $field): View
    {
        return view('admin.schedules.create', compact('field'));
    }

    public function store(ScheduleRequest $request, Field $field, GenerateFieldScheduleSlots $generateSlots): RedirectResponse
    {
        $schedule = $field->schedules()->create($request->validated());

        $generateSlots->handle($schedule);

        return redirect()
            ->route('admin.fields.schedules.index', $field)
            ->with('success', 'Jadwal dan slot waktu berhasil dibuat.');
    }

    public function destroy(FieldSchedule $schedule): RedirectResponse
    {
        $booked = $schedule->slots()->where('status', '!=', SlotStatus::Available->value)->exists();

        if ($booked) {
            return back()->with('error', 'Jadwal memiliki slot yang sudah dipesan. Batalkan booking terlebih dahulu.');
        }

        $field = $schedule->field;
        $schedule->slots()->delete();
        $schedule->delete();

        return redirect()
            ->route('admin.fields.schedules.index', $field)
            ->with('success', 'Jadwal berhasil dihapus.');
    }
}
