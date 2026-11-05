<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // đã được middleware 'auth' kiểm soát ở route
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'review' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function searchText(): string
    {
        return (string) $this->validated('q', '');
    }

    public function reviewOnly(): bool
    {
        return (bool) $this->validated('review', false);
    }

    public function limit(): int
    {
        return (int) $this->validated('limit', 50);
    }

    public function offset(): int
    {
        return (int) $this->validated('offset', 0);
    }
}
