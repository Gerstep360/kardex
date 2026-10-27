<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CargarCentralizadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdministrador() ?? false;
    }

    public function rules(): array
    {
        return ['archivo' => ['required', 'file', 'mimes:pdf', 'extensions:pdf', 'max:5120']];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Primero selecciona el PDF que quieres cargar.',
            'archivo.mimes' => 'El archivo debe ser un PDF.',
            'archivo.extensions' => 'Selecciona un archivo con extensión .pdf.',
            'archivo.max' => 'El PDF debe pesar menos de 5 MB.',
        ];
    }
}
