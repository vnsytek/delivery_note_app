<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // đã được middleware 'auth' kiểm soát ở route
    }

    public function rules(): array
    {
        return [
            'receiver' => ['nullable', 'string', 'max:100'],
            'supplier_code' => ['nullable', 'string', 'max:50'],
            'address_manual' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
            'lines' => ['nullable', 'array'],
            'lines.*.name' => ['nullable', 'string', 'max:255'],
            'lines.*.dept_code' => ['nullable', 'string', 'max:30'],
            'lines.*.remark' => ['nullable', 'string', 'max:255'],
        ];
    }
}
