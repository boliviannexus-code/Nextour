<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localized_name,
            'slug' => $this->translation()?->slug,
            'description' => $this->localized_description,
            'tours_count' => $this->when(isset($this->tours_count), $this->tours_count),
        ];
    }
}
