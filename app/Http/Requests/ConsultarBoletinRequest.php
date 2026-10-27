<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsultarBoletinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puedeConsultarBoletines() ?? false;
    }

    public function rules(): array
    {
        $gestion = $this->input('gestion', $this->route('curso')?->gestion ?? date('Y'));

        return [
            'buscar' => ['nullable', 'string', 'max:120'],
            'gestion' => ['nullable', 'integer', 'between:2000,2100'],
            'periodo_id' => ['nullable', 'integer', Rule::exists('periodos', 'id')->where('gestion', $gestion)],
            'curso_id' => ['nullable', 'integer', Rule::exists('cursos', 'id')->where('gestion', $gestion)],
        ];
    }

    public function messages(): array
    {
        return [
            'periodo_id.exists' => 'Ese trimestre no pertenece a la gestión seleccionada. Vuelve a elegirlo.',
            'curso_id.exists' => 'Ese curso no pertenece a la gestión seleccionada.',
        ];
    }
}
