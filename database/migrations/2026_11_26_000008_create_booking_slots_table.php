<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_slot_id')
                ->constrained('field_slots')
                ->restrictOnDelete();
            $table->decimal('price', 12, 2);
            $table->timestamps();

            // Satu slot hanya boleh dipakai satu booking (penjaga double booking di level DB).
            $table->unique('field_slot_id');
            $table->index('booking_id');
        });

        // Backfill booking lama (single-slot) ke booking_slots.
        DB::table('bookings')
            ->select(['id', 'field_slot_id', 'total_price', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(200, function ($bookings) {
                $rows = [];

                foreach ($bookings as $booking) {
                    if ($booking->field_slot_id === null) {
                        continue;
                    }

                    $rows[] = [
                        'booking_id' => $booking->id,
                        'field_slot_id' => $booking->field_slot_id,
                        'price' => $booking->total_price,
                        'created_at' => $booking->created_at,
                        'updated_at' => $booking->updated_at,
                    ];
                }

                if ($rows !== []) {
                    DB::table('booking_slots')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_slots');
    }
};
