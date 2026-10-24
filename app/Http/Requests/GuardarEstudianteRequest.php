<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarEstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->esAdministrador();
    }

    public function rules(): array
    {
        $estudianteId = $this->route('estudiante') ? (is_object($this->route('estudiante')) ? $this->route('estudiante')->id : $this->route('estudiante')) : null;

        return [
            'codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('estudiantes', 'codigo')->ignore($estudianteId),
            ],
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
