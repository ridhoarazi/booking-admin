<?php

namespace App\Models;

use App\Enums\SlotStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['field_schedule_id', 'start_time', 'end_time', 'status'])]
class FieldSlot extends Model
{
    use HasFactory;

    protected $table = 'field_slots';

    protected function casts(): array
    {
        return [
            'status' => SlotStatus::class,
        ];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('start_time');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(FieldSchedule::class, 'field_schedule_id');
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === SlotStatus::Available;
    }
}
