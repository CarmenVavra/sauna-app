<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoinPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user() && ! $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'purchased_on' => ['nullable', 'date_format:Y-m-d'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.required' => 'Bitte eine Anzahl eingeben.',
            'quantity.min' => 'Bitte eine Anzahl eingeben.',
        ];
    }
}
