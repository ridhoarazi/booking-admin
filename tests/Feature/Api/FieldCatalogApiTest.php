<?php

namespace Tests\Feature\Api;

use App\Models\Field;
use App\Models\FieldSchedule;
use App\Models\FieldSlot;
use App\Models\SportCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FieldCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_field_resource_exposes_absolute_image_url(): void
    {
        $field = Field::factory()->create([
            'image_path' => 'fields/lapangan-a.jpg',
            'is_active' => true,
        ]);

        $expected = Storage::disk('public')->url('fields/lapangan-a.jpg');

        $this->assertStringStartsWith('http', $expected);

        $this->getJson('/api/v1/fields/'.$field->id)
            ->assertOk()
            ->assertJsonPath('data.image_path', 'fields/lapangan-a.jpg')
            ->assertJsonPath('data.image_url', $expected);
    }

    public function test_field_resource_returns_null_image_url_without_image(): void
    {
        $field = Field::factory()->create(['image_path' => null]);

        $this->getJson('/api/v1/fields/'.$field->id)
            ->assertOk()
            ->assertJsonPath('data.image_url', null);
    }

    public function test_fields_can_be_searched_by_name_and_location(): void
    {
        SportCategory::factory()->create(['slug' => 'futsal']);

        Field::factory()->create([
            'name' => 'Apex Arena Futsal',
            'location' => 'Karawang',
        ]);
        Field::factory()->create([
            'name' => 'Gedung Badminton',
            'location' => 'Bekasi',
        ]);

        $this->getJson('/api/v1/fields?search=apex')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Apex Arena Futsal');

        $this->getJson('/api/v1/fields?search=bekasi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gedung Badminton');
    }

    public function test_search_keeps_category_filter(): void
    {
        $futsal = SportCategory::factory()->create(['slug' => 'futsal']);
        $badminton = SportCategory::factory()->create(['slug' => 'badminton']);

        Field::factory()->create([
            'name' => 'Arena Karawang',
            'sport_category_id' => $futsal->id,
        ]);
        Field::factory()->create([
            'name' => 'Arena Karawang Badminton',
            'sport_category_id' => $badminton->id,
        ]);

        $this->getJson('/api/v1/fields?category=futsal&search=karawang')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Arena Karawang')
            ->assertJsonPath('data.0.category.slug', 'futsal');
    }

    public function test_schedules_can_be_filtered_by_date(): void
    {
        Carbon::setTestNow('2026-11-26 08:30:00');

        $field = Field::factory()->create();

        $today = FieldSchedule::factory()->create([
            'field_id' => $field->id,
            'date' => '2026-11-26',
        ]);
        $tomorrow = FieldSchedule::factory()->create([
            'field_id' => $field->id,
            'date' => '2026-11-27',
        ]);

        FieldSlot::factory()->create([
            'field_schedule_id' => $today->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
        FieldSlot::factory()->create([
            'field_schedule_id' => $tomorrow->id,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $response = $this->getJson('/api/v1/fields/'.$field->id.'/schedules?date=2026-11-27');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.date', '2026-11-27')
            ->assertJsonCount(1, 'data.0.slots')
            ->assertJsonPath('data.0.slots.0.start_time', '08:00:00');

        Carbon::setTestNow();
    }

    public function test_schedules_hide_slots_that_already_started_today(): void
    {
        Carbon::setTestNow('2026-11-26 09:30:00');

        $field = Field::factory()->create();

        $today = FieldSchedule::factory()->create([
            'field_id' => $field->id,
            'date' => '2026-11-26',
        ]);
        $future = FieldSchedule::factory()->create([
            'field_id' => $field->id,
            'date' => '2026-11-27',
        ]);

        FieldSlot::factory()->create([
            'field_schedule_id' => $today->id,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);
        FieldSlot::factory()->create([
            'field_schedule_id' => $today->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
        FieldSlot::factory()->create([
            'field_schedule_id' => $today->id,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);
        FieldSlot::factory()->create([
            'field_schedule_id' => $future->id,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $this->getJson('/api/v1/fields/'.$field->id.'/schedules')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.date', '2026-11-26')
            ->assertJsonCount(1, 'data.0.slots')
            ->assertJsonPath('data.0.slots.0.start_time', '10:00:00')
            ->assertJsonPath('data.1.date', '2026-11-27')
            ->assertJsonCount(1, 'data.1.slots');

        Carbon::setTestNow();
    }

    public function test_schedule_date_filter_rejects_invalid_and_past_dates(): void
    {
        $field = Field::factory()->create();

        $this->getJson('/api/v1/fields/'.$field->id.'/schedules?date=26-11-2026')
            ->assertStatus(422);

        $this->getJson('/api/v1/fields/'.$field->id.'/schedules?date=2026-11-25')
            ->assertStatus(422);
    }

    public function test_schedules_hide_inactive_schedules(): void
    {
        Carbon::setTestNow('2026-11-26 08:30:00');

        $field = Field::factory()->create();

        $active = FieldSchedule::factory()->create([
            'field_id' => $field->id,
            'date' => '2026-11-27',
            'is_active' => true,
        ]);
        $inactive = FieldSchedule::factory()->create([
            'field_id' => $field->id,
            'date' => '2026-11-28',
            'is_active' => false,
        ]);

        FieldSlot::factory()->create([
            'field_schedule_id' => $active->id,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);
        FieldSlot::factory()->create([
            'field_schedule_id' => $inactive->id,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $this->getJson('/api/v1/fields/'.$field->id.'/schedules')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.date', '2026-11-27');

        $this->getJson('/api/v1/fields/'.$field->id.'/schedules?date=2026-11-28')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Carbon::setTestNow();
    }
}
