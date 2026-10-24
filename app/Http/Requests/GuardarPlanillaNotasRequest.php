<?php

namespace App\Http\Requests;

use App\Models\Asignacion;
use App\Models\Periodo;
use Illuminate\Foundation\Http\FormRequest;

class GuardarPlanillaNotasRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user || !$user->esDocente()) {
            return false;
        }

        // El docente debe ser el titular de la asignación
        $asignacion = Asignacion::find($this->input('asignacion_id'));
        return $asignacion && (int)$asignacion->docente_id === (int)$user->id;
    }

    public function rules(): array
    {
        $min = config('academico.escala_minima', 1);
        $max = config('academico.escala_maxima', 100);

        return [
            'asignacion_id' => ['required', 'exists:asignaciones,id'],
            'periodo_id' => ['required', 'exists:periodos,id'],
            'notas' => ['nullable', 'array'],
            'notas.*' => ['nullable', 'numeric', "min:$min", "max:$max"],
            'observaciones' => ['nullable', 'array'],
            'observaciones.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $asignacion = Asignacion::find($this->input('asignacion_id'));
            $periodo = Periodo::find($this->input('periodo_id'));

            if ($asignacion && $periodo) {
                if ((int)$asignacion->gestion !== (int)$periodo->gestion) {
                    $validator->errors()->add('periodo_id', 'La gestión del periodo no coincide con la gestión de la asignación.');
                }
            }
        });
    }

    public function messages(): array
    {
        $min = config('academico.escala_minima', 1);
        $max = config('academico.escala_maxima', 100);

        return [
            'notas.*.numeric' => 'Cada calificación debe ser un valor numérico válido.',
            'notas.*.min' => "La calificación mínima permitida es $min.",
            'notas.*.max' => "La calificación máxima permitida es $max.",
            'observaciones.*.max' => 'La observación no puede superar los 500 caracteres.',
        ];
    }
}
