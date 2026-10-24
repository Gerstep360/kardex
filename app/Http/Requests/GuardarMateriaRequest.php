<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarMateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->esAdministrador();
    }

    public function rules(): array
    {
        $materiaId = $this->route('materia') ? (is_object($this->route('materia')) ? $this->route('materia')->id : $this->route('materia')) : null;

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'sigla' => [
                'required',
                'string',
                'max:20',
                Rule::unique('materias', 'sigla')->ignore($materiaId),
            ],
        ];
    }
}
