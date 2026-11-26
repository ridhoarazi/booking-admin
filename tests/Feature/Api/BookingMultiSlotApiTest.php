<?php

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\Field;
use App\Models\FieldSchedule;
use App\Models\FieldSlot;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingMultiSlotApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array{0: Field, 1: array<int, FieldSlot>}
     */
    private function makeSlots(int $count = 3, ?Field $field = null, ?string $date = null, string $firstStart = '08:00:00'): array
    {
        $field ??= Field::factory()->create(['is_active' => true]);

        $schedule = FieldSchedule::factory()->create([
            'field_id' => $field->id,
            'date' => $date ?? now()->addDay()->toDateString(),
            'is_active' => true,
        ]);

        $slots = [];
        $start = Carbon::parse($firstStart);

        for ($i = 0; $i < $count; $i++) {
            $slots[] = FieldSlot::factory()->create([
                'field_schedule_id' => $schedule->id,
                'start_time' => $start->format('H:i:s'),
                'end_time' => $start->copy()->addHour()->format('H:i:s'),
                'status' => SlotStatus::Available,
            ]);

            $start->addHour();
        }

        return [$field, $slots];
    }

    public function test_single_slot_booking_creates_booking_slot_row(): void
    {
        $user = User::factory()->create();
        [$field, $slots] = $this->makeSlots(1);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', ['field_slot_id' => $slots[0]->id])
            ->assertCreated()
            ->assertJsonPath('data.slot_count', 1)
            ->assertJsonCount(1, 'data.slots')
            ->assertJsonPath('data.slots.0.slot_id', $slots[0]->id);

        $this->assertDatabaseHas('booking_slots', ['field_slot_id' => $slots[0]->id]);
        $this->assertDatabaseHas('field_slots', [
            'id' => $slots[0]->id,
            'status' => SlotStatus::Booked->value,
        ]);
    }

    public function test_user_can_book_multiple_slots_in_one_transaction(): void
    {
        $user = User::factory()->create();
        [$field, $slots] = $this->makeSlots(3);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_ids' => [$slots[0]->id, $slots[1]->id, $slots[2]->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', BookingStatus::Pending->value)
            ->assertJsonPath('data.slot_count', 3)
            ->assertJsonCount(3, 'data.slots')
            ->assertJsonPath('data.field.id', $field->id);

        $expected = round((float) $field->price_per_hour * 3, 2);
        $this->assertSame($expected, (float) $response->json('data.total_price'));

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('booking_slots', 3);

        foreach ($slots as $slot) {
            $this->assertDatabaseHas('field_slots', [
                'id' => $slot->id,
                'status' => SlotStatus::Booked->value,
            ]);
        }

        $booking = Booking::first();
        $this->assertSame($slots[0]->id, $booking->field_slot_id);
        $this->assertSame(3, $booking->slots()->count());
    }

    public function test_booking_fails_atomically_when_one_slot_is_already_booked(): void
    {
        $user = User::factory()->create();
        [, $slots] = $this->makeSlots(3);

        $slots[1]->update(['status' => SlotStatus::Booked]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_ids' => [$slots[0]->id, $slots[1]->id, $slots[2]->id],
            ])
            ->assertStatus(409);

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_slots', 0);

        foreach ([$slots[0], $slots[2]] as $slot) {
            $this->assertDatabaseHas('field_slots', [
                'id' => $slot->id,
                'status' => SlotStatus::Available->value,
            ]);
        }
    }

    public function test_booking_fails_atomically_when_one_slot_is_blocked(): void
    {
        $user = User::factory()->create();
        [, $slots] = $this->makeSlots(2);

        $slots[1]->update(['status' => SlotStatus::Blocked]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_ids' => [$slots[0]->id, $slots[1]->id],
            ])
            ->assertStatus(409);

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseHas('field_slots', [
            'id' => $slots[0]->id,
            'status' => SlotStatus::Available->value,
        ]);
    }

    public function test_booking_rejects_slots_from_different_fields(): void
    {
        $user = User::factory()->create();
        [, $first] = $this->makeSlots(1);
        [, $second] = $this->makeSlots(1);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_ids' => [$first[0]->id, $second[0]->id],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Semua slot harus berasal dari lapangan yang sama.');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_rejects_slot_that_already_started(): void
    {
        Carbon::setTestNow('2026-11-26 12:00:00');

        $user = User::factory()->create();
        [, $slots] = $this->makeSlots(2, null, '2026-11-26', '08:00:00');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_ids' => [$slots[0]->id, $slots[1]->id],
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Slot waktu tidak tersedia untuk dipesan.');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_rejects_inactive_schedule(): void
    {
        $user = User::factory()->create();
        [, $slots] = $this->makeSlots(2);

        $slots[0]->schedule->update(['is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_ids' => [$slots[0]->id, $slots[1]->id],
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Jadwal lapangan tidak aktif.');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_rejects_inactive_field(): void
    {
        $user = User::factory()->create();
        [$field, $slots] = $this->makeSlots(2);

        $field->update(['is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_ids' => [$slots[0]->id, $slots[1]->id],
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Lapangan sedang tidak aktif.');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_duplicate_slot_ids_are_rejected(): void
    {
        $user = User::factory()->create();
        [, $slots] = $this->makeSlots(1);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_ids' => [$slots[0]->id, $slots[0]->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('field_slot_ids.0');
    }

    public function test_booking_rejects_more_than_eight_slots(): void
    {
        $user = User::factory()->create();
        [, $slots] = $this->makeSlots(9);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_ids' => array_map(fn (FieldSlot $slot) => $slot->id, $slots),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('field_slot_ids');
    }

    public function test_booking_rejects_mixing_single_and_multi_slot_payloads(): void
    {
        $user = User::factory()->create();
        [, $slots] = $this->makeSlots(2);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_id' => $slots[0]->id,
                'field_slot_ids' => [$slots[1]->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('field_slot_id');
    }

    public function test_booking_rejects_unknown_slot(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', ['field_slot_ids' => [999999]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('field_slot_ids.0');
    }

    public function test_same_slot_cannot_be_booked_twice(): void
    {
        $user = User::factory()->create();
        [, $slots] = $this->makeSlots(1);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', ['field_slot_ids' => [$slots[0]->id]])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', ['field_slot_ids' => [$slots[0]->id]])
            ->assertStatus(409);

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('booking_slots', 1);
    }

    public function test_unique_index_blocks_duplicate_slot_rows(): void
    {
        $user = User::factory()->create();
        [, $slots] = $this->makeSlots(1);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', ['field_slot_ids' => [$slots[0]->id]])
            ->assertCreated();

        $other = Booking::factory()->create(['user_id' => $user->id]);

        $this->expectException(QueryException::class);

        DB::table('booking_slots')->insert([
            'booking_id' => $other->id,
            'field_slot_id' => $slots[0]->id,
            'price' => 10000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_cancelling_multi_slot_booking_releases_all_slots(): void
    {
        $user = User::factory()->create();
        [, $slots] = $this->makeSlots(3);

        $bookingId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_ids' => [$slots[0]->id, $slots[1]->id, $slots[2]->id],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/bookings/'.$bookingId.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value);

        foreach ($slots as $slot) {
            $this->assertDatabaseHas('field_slots', [
                'id' => $slot->id,
                'status' => SlotStatus::Available->value,
            ]);
        }

        $booking = Booking::find($bookingId);
        $this->assertNotNull($booking->cancelled_at);
        $this->assertSame(3, $booking->slots()->count());
    }

    public function test_booking_history_exposes_all_slots(): void
    {
        $user = User::factory()->create();
        [, $slots] = $this->makeSlots(2);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'field_slot_ids' => [$slots[0]->id, $slots[1]->id],
            ])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bookings')
            ->assertOk()
            ->assertJsonPath('data.0.slot_count', 2)
            ->assertJsonCount(2, 'data.0.slots')
            ->assertJsonPath('data.0.slots.0.slot_id', $slots[0]->id)
            ->assertJsonPath('data.0.slots.1.slot_id', $slots[1]->id);
    }
}
