<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            'order_id'    => 'required|exists:orders,id',
            'company_id'  => 'required|exists:shipping_companies,id',
            'awb' => 'string|nullable',
            'url' => 'string|nullable',
            'status' => 'string|nullable'
        ];
    }
}
