<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $party = $this->route('party');

        return (bool) $this->user()?->isAdmin() && $party instanceof User && ! $party->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $party */
        $party = $this->route('party');
        $apartments = (int) $party->staircase()->value('apartments');

        return [
            'first_name' => ['required', 'string', 'max:60', 'regex:/\pL/u'],
            'last_name' => ['required', 'string', 'max:60', 'regex:/\pL/u'],
            'apartment' => ['required', 'integer', 'min:1', 'max:'.max(1, $apartments)],
            'update_username' => ['sometimes', 'boolean'],
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
