<?php

namespace Tests\Feature\Admin;

use App\Actions\GenerateFieldScheduleSlots;
use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\Field;
use App\Models\FieldSchedule;
use App\Models\FieldSlot;
use App\Models\SportCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function loginAs(User $user): void
    {
        $this->actingAs($user);
    }

    public function test_login_page_loaded_by_guest(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Masuk Admin');
    }

    public function test_admin_can_login_and_reach_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'password' => bcrypt('secret')]);

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret'])
            ->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Dashboard Admin');
    }

    public function test_non_admin_cannot_login_as_admin(): void
    {
        $user = User::factory()->create(['role' => 'user', 'password' => bcrypt('secret')]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'secret'])
            ->assertSessionHasErrors('email');

        $this->get(route('admin.dashboard'))->assertStatus(302);
    }

    public function test_guest_redirected_from_admin_pages(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
    }

    public function test_user_role_blocked_by_admin_middleware(): void
    {
        $this->loginAs(User::factory()->create(['role' => 'user']));

        $this->get(route('admin.dashboard'))->assertStatus(403);
    }

    public function test_category_crud_by_admin(): void
    {
        $this->loginAs(User::factory()->create(['role' => 'admin']));

        $this->post(route('admin.categories.store'), [
            'name' => 'Futsal',
            'icon' => 'sports_soccer',
            'is_active' => true,
        ])->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('sport_categories', ['name' => 'Futsal', 'slug' => 'futsal']);

        $category = SportCategory::where('name', 'Futsal')->first();

        $this->put(route('admin.categories.update', $category), [
            'name' => 'Futsal Indoor',
            'icon' => 'sports_soccer',
            'is_active' => true,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('sport_categories', ['id' => $category->id, 'name' => 'Futsal Indoor']);

        $this->delete(route('admin.categories.destroy', $category))->assertSessionHas('success');
        $this->assertDatabaseMissing('sport_categories', ['id' => $category->id]);
    }

    public function test_field_crud_with_image_by_admin(): void
    {
        Storage::fake('public');
        $this->loginAs(User::factory()->create(['role' => 'admin']));

        $category = SportCategory::factory()->create();

        $this->post(route('admin.fields.store'), [
            'name' => 'Lapangan Futsal A',
            'slug' => 'lapangan-futsal-a',
            'sport_category_id' => $category->id,
            'location' => 'Mall XP',
            'description' => 'Indoor.',
            'price_per_hour' => 150000,
            'image' => \Illuminate\Http\UploadedFile::fake()->image('futsal.jpg'),
            'is_active' => true,
        ])->assertRedirect(route('admin.fields.index'))
            ->assertSessionHas('success');

        $field = Field::where('slug', 'lapangan-futsal-a')->first();
        $this->assertNotNull($field->image_path);

        $this->patch(route('admin.fields.update', $field), [
            'name' => 'Lapangan Futsal A',
            'slug' => 'lapangan-futsal-a',
            'sport_category_id' => $category->id,
            'location' => 'Mall XP Updated',
            'description' => 'Indoor.',
            'price_per_hour' => 150000,
            'is_active' => true,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('fields', ['id' => $field->id, 'location' => 'Mall XP Updated']);

        $this->delete(route('admin.fields.destroy', $field))->assertSessionHas('success');
        $this->assertDatabaseMissing('fields', ['id' => $field->id]);
    }

    public function test_schedule_store_generates_slots(): void
    {
        $this->loginAs(User::factory()->create(['role' => 'admin']));

        $field = Field::factory()->create();
        $date = now()->addDay()->format('Y-m-d');

        $this->post(route('admin.fields.schedules.store', $field), [
            'date' => $date,
            'open_time' => '08:00',
            'close_time' => '10:00',
            'is_active' => true,
        ])->assertRedirect(route('admin.fields.schedules.index', $field))
            ->assertSessionHas('success');

        $schedule = FieldSchedule::where('field_id', $field->id)->whereDate('date', $date)->first();
        $this->assertNotNull($schedule);
        $this->assertCount(2, $schedule->slots);
        $this->assertTrue($schedule->slots->every(fn ($s) => $s->status === SlotStatus::Available));
    }

    public function test_booking_confirm_and_cancel_by_admin(): void
    {
        $this->loginAs(User::factory()->create(['role' => 'admin']));

        $user = User::factory()->create();
        $field = Field::factory()->create();
        $schedule = FieldSchedule::factory()->create(['field_id' => $field->id, 'date' => now()->addDay()]);
        $slot = FieldSlot::factory()->create([
            'field_schedule_id' => $schedule->id,
            'status' => SlotStatus::Booked,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'field_id' => $field->id,
            'field_slot_id' => $slot->id,
            'status' => BookingStatus::Pending,
        ]);

        // Confirm
        $this->patch(route('admin.bookings.confirm', $booking))->assertSessionHas('success');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => BookingStatus::Confirmed]);

        // Cancel -> slot back to available
        $this->patch(route('admin.bookings.cancel', $booking))->assertSessionHas('success');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => BookingStatus::Cancelled]);
        $this->assertDatabaseHas('field_slots', ['id' => $slot->id, 'status' => SlotStatus::Available]);
        $this->assertNotNull($booking->fresh()->cancelled_at);
    }

    public function test_cannot_confirm_non_pending_booking(): void
    {
        $this->loginAs(User::factory()->create(['role' => 'admin']));

        $booking = Booking::factory()->create(['status' => BookingStatus::Confirmed]);

        $this->patch(route('admin.bookings.confirm', $booking))->assertSessionHas('error');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => BookingStatus::Confirmed]);
    }

    public function test_cannot_delete_category_with_fields(): void
    {
        $this->loginAs(User::factory()->create(['role' => 'admin']));

        $category = SportCategory::factory()->create();
        Field::factory()->create(['sport_category_id' => $category->id]);

        $this->delete(route('admin.categories.destroy', $category))->assertSessionHas('error');
        $this->assertDatabaseHas('sport_categories', ['id' => $category->id]);
    }

    public function test_guest_redirected_from_booking_confirm_and_cancel(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);

        $this->patch(route('admin.bookings.confirm', $booking))->assertRedirect(route('login'));
        $this->patch(route('admin.bookings.cancel', $booking))->assertRedirect(route('login'));
    }

    public function test_user_role_cannot_confirm_or_cancel_booking(): void
    {
        $this->loginAs(User::factory()->create(['role' => 'user']));

        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);

        $this->patch(route('admin.bookings.confirm', $booking))->assertStatus(403);
        $this->patch(route('admin.bookings.cancel', $booking))->assertStatus(403);
    }

    public function test_admin_cannot_cancel_non_cancelable_booking(): void
    {
        $this->loginAs(User::factory()->create(['role' => 'admin']));

        $booking = Booking::factory()->create(['status' => BookingStatus::Cancelled]);

        $this->patch(route('admin.bookings.cancel', $booking))->assertSessionHas('error');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => BookingStatus::Cancelled]);
    }
}
