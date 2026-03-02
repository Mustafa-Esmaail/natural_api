<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderCouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'                => $this->when($this->id, $this->id),
            'order_id'          => $this->when($this->order_id, $this->order_id),
            'user_id'          => $this->when($this->user_id, $this->user_id),
            'name'  => $this->when($this->name, $this->name),
            'price'  => $this->when($this->price, $this->price),


            // Relations

            'order'     => OrderResource::make($this->whenLoaded('order')),
            'user'     => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
