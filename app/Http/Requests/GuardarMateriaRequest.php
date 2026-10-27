<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarMateriaRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('orden') === null) {
            $this->merge(['orden' => 0]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() && $this->user()->esAdministrador();
    }

    public function rules(): array
    {
        $materiaId = $this->route('materia') ? (is_object($this->route('materia')) ? $this->route('materia')->id : $this->route('materia')) : null;

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'campo' => ['nullable', 'string', 'max:100'],
            'orden' => ['nullable', 'integer', 'between:0,1000'],
            'sigla' => [
                'required',
                'string',
                'max:20',
                Rule::unique('materias', 'sigla')->ignore($materiaId),
            ],
        ];
    }
}
