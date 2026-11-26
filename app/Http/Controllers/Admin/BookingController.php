<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FieldSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Booking::with(['user', 'field.sportCategory', 'slot.schedule', 'slots.slot.schedule']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('booking_code', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('date')) {
            $query->whereHas('slots.slot.schedule', fn ($schedule) => $schedule->whereDate('date', $request->date('date')));
        }

        $bookings = $query->latest()->paginate(15)->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function confirm(Booking $booking): RedirectResponse
    {
        if ($booking->status !== BookingStatus::Pending) {
            return back()->with('error', 'Hanya booking pending yang dapat dikonfirmasi.');
        }

        $booking->update(['status' => BookingStatus::Confirmed]);

        return back()->with('success', 'Booking '.$booking->booking_code.' dikonfirmasi.');
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
            return back()->with('error', 'Booking ini tidak dapat dibatalkan.');
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $slotIds = $booking->slots()->pluck('field_slot_id')
                ->push($booking->field_slot_id)
                ->filter()
                ->unique();

            FieldSlot::whereIn('id', $slotIds)->update(['status' => SlotStatus::Available]);
        });

        return back()->with('success', 'Booking '.$booking->booking_code.' dibatalkan.');
    }
}
