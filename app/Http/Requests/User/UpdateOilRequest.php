<?php

namespace App\Http\Requests\User;

use App\Models\Oil;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOilRequest extends FormRequest
{
    public function authorize(): bool
    {
        $oil = $this->route('oil');

        return $oil instanceof Oil && (bool) $this->user()?->can('update', $oil);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'brand' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:255'],
            'effect' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
