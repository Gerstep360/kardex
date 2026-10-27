<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarEstudianteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('apellido_paterno', '')) && is_string($this->input('apellido_materno', ''))) {
            $this->merge(['apellidos' => trim($this->input('apellido_paterno', '').' '.$this->input('apellido_materno', ''))]);
        }
        if ($this->has('codigo') && is_string($this->input('codigo'))) {
            $this->merge(['codigo' => strtoupper(trim($this->input('codigo')))]);
        }
    }

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
            'apellidos' => ['string', 'max:120'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
            'apellido_paterno' => ['required', 'string', 'max:120'],
            'apellido_materno' => ['required', 'string', 'max:120'],
            'genero' => ['required', Rule::in(['M', 'F'])],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'lugar_nacimiento' => ['required', 'string', 'max:20'],
            'documento_identidad' => ['required', 'string', 'max:30'],
            'volver_curso' => ['nullable', 'integer', 'exists:cursos,id'],
        ];
    }

    public function attributes(): array
    {
        return ['codigo' => 'el código RUDE', 'nombres' => 'los nombres', 'apellido_paterno' => 'el apellido paterno', 'apellido_materno' => 'el apellido materno', 'genero' => 'el género', 'fecha_nacimiento' => 'la fecha de nacimiento', 'lugar_nacimiento' => 'el lugar de nacimiento', 'documento_identidad' => 'el número de C.I.', 'estado' => 'el estado del estudiante'];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa :attribute.',
            'codigo.unique' => 'Este código RUDE ya está registrado. Busca al estudiante antes de crear otra ficha.',
            'genero.in' => 'Elige el género del estudiante.',
            'fecha_nacimiento.date' => 'Escribe una fecha de nacimiento válida.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser posterior a hoy.',
            'apellidos.max' => 'Los dos apellidos juntos no pueden superar los 120 caracteres.',
            'max.string' => ':attribute no debe superar :max caracteres.',
            'string' => 'Revisa :attribute: escribe solo texto.',
        ];
    }
}
