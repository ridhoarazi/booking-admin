<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['sport_category_id', 'name', 'slug', 'location', 'description', 'price_per_hour', 'image_path', 'is_active'])]
class Field extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_per_hour' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Field $field) {
            if (blank($field->slug)) {
                $base = Str::slug($field->name);
                $slug = $base;
                $suffix = 1;

                while (static::where('slug', $slug)
                    ->whereKeyNot($field->getKey())
                    ->exists()) {
                    $slug = $base.'-'.(++$suffix);
                }

                $field->slug = $slug;
            }
        });
    }

    public function sportCategory(): BelongsTo
    {
        return $this->belongsTo(SportCategory::class);
    }

    /**
     * Absolute URL for the field photo, resolved from the configured public disk.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (blank($this->image_path)) {
                return null;
            }

            if (Str::startsWith($this->image_path, ['http://', 'https://'])) {
                return $this->image_path;
            }

            return Storage::disk('public')->url($this->image_path);
        });
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(FieldSchedule::class)->ordered();
    }

    public function slots(): HasManyThrough
    {
        return $this->hasManyThrough(FieldSlot::class, FieldSchedule::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
