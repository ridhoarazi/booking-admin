<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => new CategoryResource($this->whenLoaded('sportCategory')),
            'location' => $this->location,
            'description' => $this->description,
            'price_per_hour' => $this->price_per_hour,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
        ];
    }
}
