<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Booking\BookingRequest;
use App\Http\Resources\Api\V1\BookingResource;
use App\Models\Booking;
use App\Models\FieldSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function index(): JsonResponse
    {
        $bookings = Auth::user()->bookings()
            ->with($this->relations())
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => BookingResource::collection($bookings),
        ]);
    }

    public function show(Booking $booking): JsonResponse
    {
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Anda tidak boleh melihat booking orang lain.'], 403);
        }

        $booking->load($this->relations());

        return response()->json([
            'data' => new BookingResource($booking),
        ]);
    }

    public function store(BookingRequest $request): JsonResponse
    {
        $slotIds = $request->slotIds();

        try {
            $booking = DB::transaction(function () use ($slotIds) {
            // Kunci semua slot dengan urutan id yang konsisten (hindari deadlock)
            // agar status slot tidak berubah antara validasi dan penyimpanan.
            $slots = FieldSlot::with('schedule.field')
                ->whereIn('id', $slotIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            abort_if($slots->count() !== count($slotIds), 404, 'Slot waktu tidak ditemukan.');

            $this->ensureSlotsAreBookable($slots);

            $field = $slots->first()->schedule->field;
            $pricePerSlot = $field->price_per_hour;

            $booking = Booking::create([
                'booking_code' => 'BK-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'user_id' => Auth::id(),
                'field_id' => $field->id,
                // Kolom lama: slot pertama. Dipakai Web Admin & klien single-slot.
                'field_slot_id' => $slots->first()->id,
                'total_price' => round((float) $pricePerSlot * $slots->count(), 2),
                'status' => BookingStatus::Pending,
            ]);

            foreach ($slots as $slot) {
                $booking->slots()->create([
                    'field_slot_id' => $slot->id,
                    'price' => $pricePerSlot,
                ]);

                $slot->update(['status' => SlotStatus::Booked]);
            }

            return $booking;
        });

        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json([
                'message' => 'Salah satu slot telah dipesan pengguna lain.',
            ], 409);
        }

        $booking->load($this->relations());

        return response()->json([
            'message' => 'Booking berhasil dibuat.',
            'data' => new BookingResource($booking),
        ], 201);
    }

    public function cancel(Booking $booking): JsonResponse
    {
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Anda tidak boleh membatalkan booking orang lain.'], 403);
        }

        if ($booking->status !== BookingStatus::Pending) {
            return response()->json(['message' => 'Hanya booking dengan status pending yang dapat dibatalkan.'], 409);
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $slots = FieldSlot::whereIn('id', $this->slotIdsOf($booking))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($slots as $slot) {
                $slot->update(['status' => SlotStatus::Available]);
            }
        });

        $booking->load($this->relations());

        return response()->json([
            'message' => 'Booking berhasil dibatalkan.',
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Validasi seluruh slot sebagai satu kesatuan: satu lapangan, aktif,
     * belum lewat, dan masih tersedia. Satu slot gagal = seluruh booking gagal.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, FieldSlot>  $slots
     */
    private function ensureSlotsAreBookable($slots): void
    {
        $fieldIds = $slots->map(fn (FieldSlot $slot) => $slot->schedule->field_id)->unique();

        abort_if(
            $fieldIds->count() > 1,
            422,
            'Semua slot harus berasal dari lapangan yang sama.'
        );

        $today = now()->toDateString();
        $time = now()->format('H:i:s');

        foreach ($slots as $slot) {
            $schedule = $slot->schedule;

            abort_if(! $schedule->is_active, 409, 'Jadwal lapangan tidak aktif.');

            abort_if(! $schedule->field->is_active, 409, 'Lapangan sedang tidak aktif.');

            $date = $schedule->date->toDateString();

            abort_if(
                $date < $today || ($date === $today && $slot->start_time <= $time),
                409,
                'Slot waktu tidak tersedia untuk dipesan.'
            );

            abort_if($slot->status !== SlotStatus::Available, 409, 'Slot telah dipesan pengguna lain.');
        }
    }

    /**
     * ID slot milik booking, termasuk kolom lama `field_slot_id` untuk data historis.
     *
     * @return array<int, int>
     */
    private function slotIdsOf(Booking $booking): array
    {
        return $booking->slots()
            ->pluck('field_slot_id')
            ->push($booking->field_slot_id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return ['field.sportCategory', 'slot.schedule', 'slots.slot.schedule', 'user'];
    }
}
