<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('q') && ! $this->filled('search')) {
            $this->merge([
                'search' => $this->input('q'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'search' => ['sometimes', 'string', 'max:255'],
            'q' => ['sometimes', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'expired' => ['sometimes', 'boolean'],
            'ability' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', Rule::in(['created_at', 'name', 'expires_at', 'last_used_at'])],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->integer('per_page', 15);
    }

    public function sortColumn(): string
    {
        return $this->validated('sort', 'created_at');
    }

    public function sortDirection(): string
    {
        return $this->validated('direction', 'desc');
    }
}
