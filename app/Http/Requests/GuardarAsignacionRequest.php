<?php

namespace App\Http\Requests;

use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarAsignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->esAdministrador();
    }

    public function rules(): array
    {
        $asignacionId = $this->route('asignacion') ? (is_object($this->route('asignacion')) ? $this->route('asignacion')->id : $this->route('asignacion')) : null;

        return [
            'docente_id' => ['required', 'exists:usuarios,id'],
            'materia_id' => ['required', 'exists:materias,id'],
            'curso_id' => ['required', 'exists:cursos,id'],
            'gestion' => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Verificar que el usuario sea docente y esté activo
            $docente = Usuario::find($this->input('docente_id'));
            if ($docente) {
                if ($docente->rol !== 'docente') {
                    $validator->errors()->add('docente_id', 'El usuario seleccionado debe tener el rol de docente.');
                }
                if (!$docente->activo) {
                    $validator->errors()->add('docente_id', 'El docente seleccionado está inactivo.');
                }
            }

            // Verificar que la gestión de la asignación coincida con la del curso
            $curso = Curso::find($this->input('curso_id'));
            if ($curso && (int)$curso->gestion !== (int)$this->input('gestion')) {
                $validator->errors()->add('gestion', 'La gestión seleccionada no coincide con la gestión del curso.');
            }

            // Verificar unicidad de (curso_id, materia_id, gestion)
            $asignacionId = $this->route('asignacion') ? (is_object($this->route('asignacion')) ? $this->route('asignacion')->id : $this->route('asignacion')) : null;
            $existe = \App\Models\Asignacion::where('curso_id', $this->input('curso_id'))
                ->where('materia_id', $this->input('materia_id'))
                ->where('gestion', $this->input('gestion'))
                ->when($asignacionId, fn($q) => $q->where('id', '!=', $asignacionId))
                ->exists();

            if ($existe) {
                $validator->errors()->add('materia_id', 'Ya existe una asignación para esta materia y curso en la misma gestión.');
            }
        });
    }
}
