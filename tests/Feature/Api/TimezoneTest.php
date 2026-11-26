<?php

namespace Tests\Feature\Api;

use App\Models\Field;
use App\Models\FieldSchedule;
use App\Models\FieldSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_application_runs_on_jakarta_timezone(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->timezoneName);
    }

    public function test_utc_evening_is_next_day_in_jakarta(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-26 17:30:00', 'UTC'));

        $this->assertSame('2026-11-27', now()->toDateString());
        $this->assertSame('00:30:00', now()->format('H:i:s'));
    }

    public function test_schedule_today_uses_jakarta_date_and_hides_started_slots(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-26 17:30:00', 'UTC'));

        $field = Field::factory()->create();
        $schedule = FieldSchedule::factory()->create([
            'field_id' => $field->id,
            'date' => '2026-11-27',
        ]);

        FieldSlot::factory()->create([
            'field_schedule_id' => $schedule->id,
            'start_time' => '00:00:00',
            'end_time' => '01:00:00',
        ]);
        FieldSlot::factory()->create([
            'field_schedule_id' => $schedule->id,
            'start_time' => '01:00:00',
            'end_time' => '02:00:00',
        ]);

        $this->getJson('/api/v1/fields/'.$field->id.'/schedules')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.date', '2026-11-27')
            ->assertJsonCount(1, 'data.0.slots')
            ->assertJsonPath('data.0.slots.0.start_time', '01:00:00');
    }
}
