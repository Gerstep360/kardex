<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->esAdministrador();
    }

    public function rules(): array
    {
        $usuarioId = $this->route('usuario') ? (is_object($this->route('usuario')) ? $this->route('usuario')->id : $this->route('usuario')) : null;

        $rules = [
            'nombre' => ['required', 'string', 'max:150'],
            'usuario' => [
                'required',
                'string',
                'max:50',
                Rule::unique('usuarios', 'usuario')->ignore($usuarioId),
            ],
            'rol' => ['required', Rule::in(['administrador', 'docente', 'secretaria'])],
            'activo' => ['required', 'boolean'],
        ];

        if ($this->isMethod('post')) {
            $rules['password'] = ['required', 'string', 'min:6'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:6'];
        }

        return $rules;
    }
}
