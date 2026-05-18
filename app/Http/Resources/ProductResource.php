<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (int) $this->price,
            'description' => $this->whenHas('description', fn () => $this->description),
            'modifiers' => $this->modifiers ?? collect(),
            'image_url' => $this->whenHas('image', fn () => str_starts_with((string) $this->image, 'http') ? $this->image : asset('storage/'.$this->image)),
            'is_available' => $this->is_available,
            'weight_g' => $this->whenHas('weight_g', fn () => $this->weight_g),
        ];
    }
}
