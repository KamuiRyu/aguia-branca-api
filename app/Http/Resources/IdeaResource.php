<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdeaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->title,
            'descricao' => $this->description,
            'categoria' => $this->category,
            'setor' => $this->sector,
            'impactoEsperado' => $this->expected_impact,
            'urgencia' => $this->urgency,
            'status' => $this->status,
            'autor' => $this->author_name ?? ($this->user?->name),
            'autorNome' => $this->author_name,
            'autorId' => $this->author_registration_id,
            'userId' => $this->user_id,
            'avaliadorId' => $this->evaluated_by,
            'gestorResponsavel' => $this->manager_responsible_name,
            'observacoes' => $this->evaluation_notes,
            'dataCriacao' => $this->created_at?->toIso8601String(),
            'criadoEm' => $this->created_at?->toIso8601String(),
        ];
    }
}
