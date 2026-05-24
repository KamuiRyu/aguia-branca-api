<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIdeaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string'],
            'categoria' => ['nullable', 'string'],
            'setor' => ['nullable', 'string'],
            'impactoEsperado' => ['nullable', 'string'],
            'urgencia' => ['nullable', 'string'],
            'autorNome' => ['nullable', 'string'],
            'autorId' => ['nullable', 'string'],
        ];
    }
}
