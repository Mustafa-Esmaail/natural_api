<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class ShippingCompanyResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var City|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'                   =>(int) $this->id,
            'title'                => $this->when($this->title, $this->title),
            'price'                => (int)$this->price,
            'cash_on_delivery'     => (bool)$this->cash_on_delivery,
            'minimum_amount'       => (int)$this->minimum_amount,
            'cash_fee'             => (int)$this->cash_fee,
            'created_at'           => $this->when($this->created_at, $this->created_at->format('Y-m-d')),
            'updated_at'           => $this->when($this->updated_at, $this->updated_at->format('Y-m-d')),
            // Relations
            'translation'          => TranslationResource::make($this->whenLoaded('translation')),
            'translations'         => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'              => $this->when($locales, $locales),
            'cities'               => CityResource::collection($this->whenLoaded('cities')),
        ];
    }
}
