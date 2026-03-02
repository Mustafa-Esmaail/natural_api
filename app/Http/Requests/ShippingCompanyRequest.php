<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ShippingCompanyRequest extends FormRequest
{
  /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {

        return [
            'title'               => ['required', 'string', 'min:1', 'max:191'],
            'description'           => 'array',
            'description.*'         => 'string|min:1',
            'minimum_amount'        => 'required|numeric',
            'cash_fee'              => 'numeric|nullable',
            'price'                 => 'required|numeric',
            'cash_on_delivery'      => 'required|in:0,1',
            'cities'         => 'array',
            'cities.*'       => [
                'required',
                'integer',
                Rule::exists('cities', 'id')
            ],
        ];
    }
}
