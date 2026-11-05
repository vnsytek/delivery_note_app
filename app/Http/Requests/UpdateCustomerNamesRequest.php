<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerNamesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // đã được middleware 'auth' kiểm soát ở route
    }

    public function rules(): array
    {
        return [
            'MA002' => ['sometimes', 'nullable', 'string', 'max:255'],
            'MA003' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
