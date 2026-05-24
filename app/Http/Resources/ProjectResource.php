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
        // Map database enum to user-facing strings
        $statusMap = [
            'PLANEJADO' => 'Planejamento',
            'EM_ANDAMENTO' => 'Em andamento',
            'CONCLUIDO' => 'Concluído',
            'CANCELADO' => 'Cancelado',
        ];

        $status = $statusMap[$this->status] ?? $this->status;

        // Convert percentage to float ratio (60 -> 0.60)
        $progresso = is_null($this->progress_percentage) ? null : (float) ($this->progress_percentage / 100);

        // Convert ROI percentage to float ratio (25.00 -> 0.25)
        $roiEsperado = is_null($this->expected_roi) ? null : (float) ($this->expected_roi / 100);

        $isList = $request->route()?->getActionMethod() === 'index';

        $data = [
            'id' => (string) $this->id,
            'titulo' => $this->name,
            'status' => $status,
            'progresso' => $progresso,
            'roiEsperado' => $roiEsperado,
        ];

        if (! $isList) {
            $data['responsavel'] = $this->responsible_team;
            $data['investimento'] = is_null($this->budget) ? null : (float) $this->budget;
            $data['prazo'] = $this->deadline?->toDateString();
            $data['descricao'] = $this->description;
            $data['dataAtualizacao'] = $this->updated_at?->toIso8601String();
        }

        return $data;
    }
}
