<?php

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\Field;
use App\Models\FieldSchedule;
use App\Models\FieldSlot;
use App\Models\SportCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_and_fields_are_public(): void
    {
        $categories = SportCategory::factory()->count(4)->create();
        Field::factory()->count(5)->create([
            'is_active' => true,
            'sport_category_id' => $categories->first()->id,
        ]);

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(4, 'data');

        $this->getJson('/api/v1/fields')->assertOk()->assertJsonCount(5, 'data');

        $field = Field::first();
        $this->getJson('/api/v1/fields/'.$field->id)->assertOk()
            ->assertJsonPath('data.id', $field->id);

        $this->getJson('/api/v1/fields/'.$field->id.'/schedules')->assertOk();
    }

    public function test_authenticated_user_can_create_booking(): void
    {
        $user = User::factory()->create();
        $slot = FieldSlot::factory()->create([
            'status' => SlotStatus::Available,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', ['field_slot_id' => $slot->id])
            ->assertCreated()
            ->assertJsonPath('data.status', BookingStatus::Pending->value)
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('field_slots', [
            'id' => $slot->id,
            'status' => SlotStatus::Booked->value,
        ]);
    }

    public function test_double_booking_is_prevented(): void
    {
        $user = User::factory()->create();
        $slot = FieldSlot::factory()->create([
            'status' => SlotStatus::Available,
        ]);

        Booking::factory()->create([
            'user_id' => $user->id,
            'field_id' => $slot->schedule->field_id,
            'field_slot_id' => $slot->id,
            'status' => BookingStatus::Pending,
        ]);
        $slot->refresh()->update(['status' => SlotStatus::Booked]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', ['field_slot_id' => $slot->id])
            ->assertStatus(409);
    }

    public function test_user_can_only_see_own_bookings(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Booking::factory()->create(['user_id' => $user->id]);
        Booking::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bookings')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_cannot_access_another_users_booking(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $booking = Booking::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bookings/'.$booking->id)
            ->assertStatus(403);
    }

    public function test_user_can_cancel_own_pending_booking(): void
    {
        $user = User::factory()->create();
        $slot = FieldSlot::factory()->create(['status' => SlotStatus::Booked]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'field_slot_id' => $slot->id,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/bookings/'.$booking->id.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value);

        $this->assertDatabaseHas('field_slots', [
            'id' => $slot->id,
            'status' => SlotStatus::Available->value,
        ]);
        $this->assertNotNull($booking->fresh()->cancelled_at);
    }

    public function test_cannot_cancel_confirmed_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::Confirmed,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/bookings/'.$booking->id.'/cancel')
            ->assertStatus(409);
    }

    public function test_unauthenticated_cannot_use_booking_endpoints(): void
    {
        $this->postJson('/api/v1/bookings', ['field_slot_id' => 1])->assertStatus(401);
        $this->getJson('/api/v1/bookings')->assertStatus(401);
    }

    public function test_booking_rejects_slot_on_inactive_schedule(): void
    {
        $user = User::factory()->create();
        $slot = FieldSlot::factory()->create(['status' => SlotStatus::Available]);

        $slot->schedule->update(['is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', ['field_slot_id' => $slot->id])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Jadwal lapangan tidak aktif.');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_rejects_slot_on_inactive_field(): void
    {
        $user = User::factory()->create();
        $slot = FieldSlot::factory()->create(['status' => SlotStatus::Available]);

        $slot->schedule->field->update(['is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', ['field_slot_id' => $slot->id])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Lapangan sedang tidak aktif.');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_rejects_slot_that_already_started_today(): void
    {
        Carbon::setTestNow('2026-11-26 10:30:00');

        $user = User::factory()->create();
        $schedule = FieldSchedule::factory()->create(['date' => '2026-11-26']);
        $slot = FieldSlot::factory()->create([
            'field_schedule_id' => $schedule->id,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => SlotStatus::Available,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bookings', ['field_slot_id' => $slot->id])
            ->assertStatus(409);

        Carbon::setTestNow();
    }
}
