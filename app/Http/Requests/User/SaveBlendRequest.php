<?php

namespace App\Http\Requests\User;

use App\Models\Blend;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Mischung anlegen (POST) bzw. ändern (PUT, mit {blend} in der Route).
 */
class SaveBlendRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || $user->isAdmin()) {
            return false;
        }
        $blend = $this->blend();

        return $blend === null || $user->can('update', $blend);
    }

    protected function prepareForValidation(): void
    {
        $ingredients = collect((array) $this->input('ingredients', []))
            ->filter(fn ($i) => is_array($i) && trim((string) ($i['oil_name'] ?? '')) !== '')
            ->map(fn (array $i) => [
                'oil_name' => trim((string) $i['oil_name']),
                'drops' => $i['drops'] ?? null,
                'note' => isset($i['note']) && trim((string) $i['note']) !== '' ? trim((string) $i['note']) : null,
            ])
            ->values()->all();

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'ingredients' => $ingredients,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('blends', 'name')->where('user_id', $this->user()?->id)->ignore($this->blend()?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'ingredients' => ['required', 'array', 'min:1', 'max:8'],
            'ingredients.*.oil_name' => ['required', 'string', 'max:60', 'regex:/\pL/u'],
            'ingredients.*.drops' => ['required', 'integer', 'min:1', 'max:20'],
            'ingredients.*.note' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bitte einen Namen für die Mischung angeben.',
            'name.unique' => 'Eine Mischung mit diesem Namen gibt es schon.',
            'ingredients.required' => 'Bitte mindestens ein Öl angeben.',
            'ingredients.min' => 'Bitte mindestens ein Öl angeben.',
            'ingredients.max' => 'Höchstens 8 Öle pro Mischung.',
            'ingredients.*.drops.*' => 'Tropfen: bitte eine Zahl von 1 bis 20.',
            'ingredients.*.oil_name.regex' => 'Bitte einen gültigen Ölnamen angeben.',
        ];
    }

    /**
     * Jedes Öl nur einmal (ohne Groß-/Kleinschreibung).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $names = array_map(fn (array $i) => mb_strtolower($i['oil_name']), (array) $this->input('ingredients'));
                if (count($names) !== count(array_unique($names))) {
                    $validator->errors()->add('ingredients', 'Jedes Öl bitte nur einmal in die Mischung aufnehmen.');
                }
            },
        ];
    }

    public function blend(): ?Blend
    {
        $blend = $this->route('blend');

        return $blend instanceof Blend ? $blend : null;
    }
}
