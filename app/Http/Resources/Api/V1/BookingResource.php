<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'field' => new FieldResource($this->whenLoaded('field')),
            'slot' => [
                'id' => $this->slot?->id,
                'start_time' => $this->slot?->start_time,
                'end_time' => $this->slot?->end_time,
                'schedule_date' => $this->slot?->schedule?->date?->toDateString(),
                'status' => $this->slot?->status?->value,
            ],
            'slot_count' => $this->whenLoaded('slots', fn () => $this->slots->count()),
            'slots' => $this->whenLoaded('slots', fn () => $this->slots->map(fn ($item) => [
                'booking_slot_id' => $item->id,
                'slot_id' => $item->field_slot_id,
                'start_time' => $item->slot?->start_time,
                'end_time' => $item->slot?->end_time,
                'schedule_date' => $item->slot?->schedule?->date?->toDateString(),
                'status' => $item->slot?->status?->value,
                'price' => $item->price,
            ])->values()->all()),
            'total_price' => $this->total_price,
            'status' => $this->status?->value,
            'created_at' => $this->created_at,
            'cancelled_at' => $this->whenNotNull($this->cancelled_at),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
