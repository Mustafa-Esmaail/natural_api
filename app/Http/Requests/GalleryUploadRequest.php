<?php
declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Gallery;
use Illuminate\Validation\Rule;

class GalleryUploadRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpeg,png,jpg,gif,pdf', 'max:5120'], // 5MB max
            'type'   => ['required', 'string', Rule::in(Gallery::TYPES)],
        ];
    }
}

