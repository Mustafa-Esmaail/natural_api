<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\PaymentReceipt;
use Illuminate\Http\Request;
class PaymentReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request)
    {

     /** @var Payment|JsonResource $this */

        return [
            'id'              => $this->id,
            'user_id'         => $this->when($this->user_id, (int) $this->order_id),
            'order_id'        => $this->when($this->input, (int) $this->order_id),            'order_id' => $this->when($this->input, (int) $this->order_id),
            'img'             => $this->when($this->img, $this->img),
            'created_at'      => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'      => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

        ];
    }
}
