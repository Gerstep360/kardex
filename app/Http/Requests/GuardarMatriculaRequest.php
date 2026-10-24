<?php

namespace App\Http\Requests;

use App\Models\Curso;
use App\Models\Estudiante;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarMatriculaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->esAdministrador();
    }

    public function rules(): array
    {
        $matriculaId = $this->route('matricula') ? (is_object($this->route('matricula')) ? $this->route('matricula')->id : $this->route('matricula')) : null;

        return [
            'estudiante_id' => [
                'required',
                'exists:estudiantes,id',
                Rule::unique('matriculas', 'estudiante_id')
                    ->where('gestion', $this->input('gestion'))
                    ->ignore($matriculaId),
            ],
            'curso_id' => ['required', 'exists:cursos,id'],
            'gestion' => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Verificar que el estudiante esté activo
            $estudiante = Estudiante::find($this->input('estudiante_id'));
            if ($estudiante && $estudiante->estado !== 'activo') {
                $validator->errors()->add('estudiante_id', 'No se puede matricular a un estudiante inactivo.');
            }

            // Verificar que la gestión de la matrícula coincida con la del curso
            $curso = Curso::find($this->input('curso_id'));
            if ($curso && (int)$curso->gestion !== (int)$this->input('gestion')) {
                $validator->errors()->add('gestion', 'La gestión seleccionada no coincide con la gestión del curso.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'estudiante_id.unique' => 'El estudiante ya cuenta con una matrícula registrada para esta gestión.',
        ];
    }
}
