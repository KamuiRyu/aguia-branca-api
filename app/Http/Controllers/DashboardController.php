<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get the dashboard metrics for leadership.
     */
    public function index(Request $request): JsonResponse
    {
        $totalIdeias = Idea::count();
        $ideiasAprovadas = Idea::where('status', 'APROVADA')->count();
        $ideiasPendentes = Idea::whereIn('status', ['PENDENTE_ANALISE', 'EM_ANALISE'])->count();
        $projetosAtivos = Project::where('status', 'EM_ANDAMENTO')->count();
        $projetosConcluidos = Project::where('status', 'CONCLUIDO')->count();

        $taxaAprovacao = $totalIdeias > 0
            ? round(($ideiasAprovadas / $totalIdeias) * 100, 2)
            : 0.0;

        return response()->json([
            'totalIdeias' => $totalIdeias,
            'ideiasAprovadas' => $ideiasAprovadas,
            'ideiasPendentes' => $ideiasPendentes,
            'projetosAtivos' => $projetosAtivos,
            'projetosConcluidos' => $projetosConcluidos,
            'indicadores' => [
                [
                    'nome' => 'Taxa de aprovação',
                    'valor' => "{$taxaAprovacao}%",
                ],
            ],
        ]);
    }
}
