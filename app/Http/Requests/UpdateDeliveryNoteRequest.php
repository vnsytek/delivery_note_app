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
            'lines.*.name_zh' => ['nullable', 'string', 'max:255'],
            'lines.*.dept_code' => ['nullable', 'string', 'max:30'],
            'lines.*.remark' => ['nullable', 'string', 'max:255'],
            // Số lượng sửa tay khi in: số không âm, tối đa 3 chữ số thập phân (dấu chấm hoặc phẩy).
            'lines.*.quantity' => ['nullable', 'regex:/^\s*\d{1,12}([.,]\d{1,3})?\s*$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.*.quantity.regex' => 'Số lượng phải là số không âm, tối đa 3 chữ số sau dấu thập phân (vd 2 hoặc 1,5).',
        ];
    }
}
