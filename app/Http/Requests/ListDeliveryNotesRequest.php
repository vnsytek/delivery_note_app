<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListDeliveryNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // đã được middleware 'auth' kiểm soát ở route
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
