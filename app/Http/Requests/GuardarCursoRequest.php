<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarCursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->esAdministrador();
    }

    public function rules(): array
    {
        $cursoId = $this->route('curso') ? (is_object($this->route('curso')) ? $this->route('curso')->id : $this->route('curso')) : null;

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'paralelo' => ['required', 'string', 'max:20'],
            'gestion' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('cursos', 'gestion')->where(function ($query) {
                    return $query->where('nombre', $this->input('nombre'))
                        ->where('paralelo', $this->input('paralelo'));
                })->ignore($cursoId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'gestion.unique' => 'Ya existe un curso con el mismo nombre y paralelo en esta gestión.',
        ];
    }
}
