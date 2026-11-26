<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'field_id', 'field_slot_id', 'booking_code', 'total_price', 'status', 'notes', 'cancelled_at'])]
class Booking extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'status' => BookingStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public function scopePending($query)
    {
        return $query->where('status', BookingStatus::Pending->value);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', BookingStatus::Confirmed->value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(FieldSlot::class, 'field_slot_id');
    }

    /**
     * Semua slot dalam booking ini (multi-slot).
     */
    public function slots(): HasMany
    {
        return $this->hasMany(BookingSlot::class);
    }

    public function canCancel(): bool
    {
        return $this->status === BookingStatus::Pending
            || $this->status === BookingStatus::Confirmed;
    }

    public function isUpcoming(): bool
    {
        $slot = $this->relationLoaded('slots')
            ? $this->slots->sortBy(fn (BookingSlot $item) => $item->slot?->schedule?->date?->toDateString().' '.$item->slot?->start_time)->first()?->slot
            : $this->slot;

        if (! $slot?->schedule) {
            return false;
        }

        $date = $slot->schedule->date->toDateString();
        $start = $slot->start_time;

        return $date > now()->toDateString()
            || ($date === now()->toDateString() && $start > now()->toTimeString());
    }
}
