<?php

namespace App\Http\Requests\Admin;

use App\Models\Staircase;
use Illuminate\Foundation\Http\FormRequest;

class StorePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Staircase $staircase */
        $staircase = $this->route('staircase');

        return [
            'first_name' => ['required', 'string', 'max:60', 'regex:/\pL/u'],
            'last_name' => ['required', 'string', 'max:60', 'regex:/\pL/u'],
            'apartment' => ['required', 'integer', 'min:1', 'max:'.$staircase->apartments],
            'password' => ['required', 'string', 'min:8', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.regex' => 'Der Vorname muss Buchstaben enthalten.',
            'last_name.regex' => 'Der Nachname muss Buchstaben enthalten.',
        ];
    }
}
