<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShippingOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return
            [
                'id'                => $this->id,
                'company_id'        => $this->when($this->company_id, $this->company_id),
                'order_id'          => $this->when($this->order_id, $this->order_id),
                'awb'               =>  $this->when($this->awb, $this->awb),
                'url'               =>  $this->when($this->url, $this->url),
                'status'            =>  $this->when($this->status, $this->status),
                // Relations
                'order'             => OrderResource::make($this->whenLoaded('order')),
                'shippingCompany'   => ShippingCompanyResource::make($this->whenLoaded('shippingCompany')),

            ];
    }
}
