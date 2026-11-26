<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['field_id', 'date', 'open_time', 'close_time', 'is_active'])]
class FieldSchedule extends Model
{
    use HasFactory;

    protected $table = 'field_schedules';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('date')->orderBy('open_time');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(FieldSlot::class)->ordered();
    }
}
