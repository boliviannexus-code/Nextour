<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $minPrice = $this->relationLoaded('prices') ? $this->prices->min('price_usd') : null;
        $mainImage = $this->relationLoaded('images') ? $this->images->first()?->url : null;

        return [
            'id' => $this->id,
            'title' => $this->display_title,
            'slug' => $this->localized_slug,
            'short_description' => $this->localized_short_description,
            'full_description' => $this->localized_description,
            'location' => [
                'country' => $this->country,
                'city' => $this->city,
                'text' => $this->location_text,
                'meeting_point' => $this->meeting_point,
            ],
            'duration' => $this->duration,
            'guide_type' => $this->whenLoaded('guideType', fn () => [
                'id' => $this->guideType?->id,
                'title' => $this->guideType?->localized_title,
            ]),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'main_image_url' => $mainImage,
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image): array => [
                'id' => $image->id,
                'url' => $image->url,
                'is_main' => $image->is_main,
            ])->values()),
            'price_from_usd' => $minPrice === null ? null : (float) $minPrice,
            'rating' => isset($this->reviews_avg_rating) ? round((float) $this->reviews_avg_rating, 1) : null,
            'reviews_count' => $this->reviews_count ?? 0,
            'minimum_capacity' => $this->minimum_capacity,
            'capacity' => $this->capacity,
            'availability' => TourAvailabilityResource::collection($this->whenLoaded('availabilities')),
            'includes' => $this->translated('includes', fallback: $this->includes ?: $this->included),
            'excludes' => $this->translated('excludes', fallback: $this->excludes ?: $this->not_included),
            'itinerary' => $this->whenLoaded('itineraryDays', fn () => $this->itineraryDays->map(fn ($day): array => [
                'day_number' => $day->day_number,
                'title' => $day->title,
                'summary' => $day->summary,
                'stops' => $day->relationLoaded('stops') ? $day->stops->map(fn ($stop): array => [
                    'position' => $stop->position,
                    'start_time' => $stop->start_time,
                    'title' => $stop->title,
                    'location_name' => $stop->location_name,
                    'activity_type' => $stop->relationLoaded('activityType') ? $stop->activityType?->localized_title : null,
                ])->values() : [],
            ])->values()),
        ];
    }
}
