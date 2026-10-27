<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmarImportacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return [
            'confirmar' => ['accepted'],
            'crear_faltantes' => ['sometimes', 'boolean'],
            'politica' => ['required', Rule::in(['conservar', 'reemplazar'])],
            'materias' => ['required', 'array', 'max:40'],
            'materias.*' => ['required', 'string', 'max:30'],
            'docentes' => ['required', 'array', 'max:40'],
            'docentes.*' => ['required', 'integer', Rule::exists('usuarios', 'id')->where('rol', 'docente')->where('activo', true)],
            'estudiantes' => ['sometimes', 'array', 'max:500'],
            'estudiantes.*' => ['array:apellido_paterno,apellido_materno,genero,fecha_nacimiento,lugar_nacimiento,documento_identidad'],
            'estudiantes.*.apellido_paterno' => ['nullable', 'string', 'max:120'],
            'estudiantes.*.apellido_materno' => ['nullable', 'string', 'max:120'],
            'estudiantes.*.genero' => ['nullable', Rule::in(['M', 'F'])],
            'estudiantes.*.fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'estudiantes.*.lugar_nacimiento' => ['nullable', 'string', 'max:20'],
            'estudiantes.*.documento_identidad' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmar.accepted' => 'Marca la casilla para confirmar que revisaste la información.',
            'docentes.*.required' => 'Selecciona el docente responsable de cada materia.',
            'docentes.*.exists' => 'Cada materia necesita un docente activo.',
            'materias.*.required' => 'Revisa la materia que corresponde a cada columna del PDF.',
            'estudiantes.*.genero.in' => 'Elige el género del estudiante.',
            'estudiantes.*.fecha_nacimiento.date' => 'Escribe una fecha de nacimiento válida.',
            'estudiantes.*.fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser posterior a hoy.',
            'estudiantes.*.lugar_nacimiento.max' => 'El lugar de nacimiento no puede superar los 20 caracteres.',
            'estudiantes.*.documento_identidad.max' => 'El número de C.I. no puede superar los 30 caracteres.',
            'estudiantes.*.array' => 'Revisa los datos del estudiante que estás completando.',
        ];
    }
}
