<?php

namespace App\Http\Requests\User;

use App\Models\Oil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user() && ! $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'brand' => $this->filled('brand') ? trim((string) $this->input('brand')) : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'effect' => $this->filled('effect') ? trim((string) $this->input('effect')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'brand' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:255'],
            'effect' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bitte einen Namen für das Öl angeben.',
        ];
    }

    /**
     * Doppelte Namen (ohne Groß-/Kleinschreibung) werden abgelehnt.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || $this->boolean('select_existing')) {
                    return;
                }
                if ($this->existingOil() !== null) {
                    $validator->errors()->add('name', $this->input('name').' ist schon in der Liste.');
                }
            },
        ];
    }

    /** Bereits vorhandenes Öl gleichen Namens (für die Auswahl im Tagebuch). */
    public function existingOil(): ?Oil
    {
        return $this->user()?->oils()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $this->input('name'))])
            ->first();
    }
}
