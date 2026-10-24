<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarPeriodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->esAdministrador();
    }

    public function rules(): array
    {
        $periodoId = $this->route('periodo') ? (is_object($this->route('periodo')) ? $this->route('periodo')->id : $this->route('periodo')) : null;

        return [
            'nombre' => ['required', 'string', 'max:80'],
            'numero' => [
                'required',
                'integer',
                'min:1',
                'max:12',
                Rule::unique('periodos', 'numero')
                    ->where('gestion', $this->input('gestion'))
                    ->ignore($periodoId),
            ],
            'gestion' => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero.unique' => 'Ya existe un periodo con este número en la gestión seleccionada.',
        ];
    }
}
