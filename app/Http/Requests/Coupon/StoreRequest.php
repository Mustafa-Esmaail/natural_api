<?php
declare(strict_types=1);

namespace App\Http\Requests\Coupon;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name'          => [
                'required',
                'string',
                Rule::unique('coupons', 'name')->ignore(request()->route('coupon'))
            ],
            'type'          => ['required', 'string', Rule::in('fix', 'percent')],
            'for'           => ['string', Rule::in('total_price', 'delivery_fee')],
            'qty'           => 'required|numeric|min:1',
            'price'         => 'required|numeric|min:1',
            'expired_at'            => 'required|date_format:Y-m-d H:i:s',
            'start_date'            => 'required|date_format:Y-m-d H:i:s',            'images'        => ['array'],
            'images.*'      => ['string'],
            'title'         => ['required', 'array'],
            'title.*'       => ['required', 'string', 'min:2', 'max:191'],
            'description'   => ['array'],
            'description.*' => ['string', 'min:2'],
            'max_discount_value'    => 'nullable|numeric',
            'max_uses_per_user'     => 'nullable|numeric',
            'min_amount_of_cart'    => 'nullable|numeric',
            'allowed_users'         => 'nullable|array',
            'specific_products'     => 'nullable|array',
            'specific_brands'       => 'nullable|array',
            'payment_methods'       => 'nullable|array',
            'categories'            => 'nullable|array',
            'commission'            => 'required' ,
        ];
    }
}

