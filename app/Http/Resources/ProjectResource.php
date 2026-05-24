<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'nome' => $this->name,
            'status' => $this->status,
            'ideiaOrigemId' => $this->idea_origin_id,
            'equipeResponsavel' => $this->responsible_team,
            'orcamento' => $this->budget,
            'prazo' => $this->deadline?->toDateString(),
            'percentualProgresso' => $this->progress_percentage,
            'roiEsperado' => $this->expected_roi,
            'descricao' => $this->description,
        ];
    }
}
