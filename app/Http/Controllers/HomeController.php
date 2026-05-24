<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Get home view payload for Operador profile.
     */
    public function operador(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'saudacao' => "Olá, {$user->name}!",
            'cardFixo' => [
                'titulo' => 'Envie suas ideias',
                'descricao' => 'Colabore com a inovação do Sistema Águia Branca.',
            ],
            'botoesAcessoRapido' => [
                ['label' => 'Cadastrar Ideia', 'rota' => '/ideias/nova'],
                ['label' => 'Minhas Ideias', 'rota' => '/ideias/minhas'],
            ],
        ]);
    }

    /**
     * Get home view payload for Gestor profile.
     */
    public function gestor(Request $request): JsonResponse
    {
        $user = $request->user();
        $notificacoesCount = Idea::whereIn('status', ['PENDENTE_ANALISE', 'EM_ANALISE'])->count();

        return response()->json([
            'saudacao' => "Olá, {$user->name}!",
            'cardFixo' => [
                'titulo' => 'Análise de Ideias',
                'descricao' => 'Gerencie as ideias enviadas pelos operadores e faça avaliações.',
            ],
            'botoesAcessoRapido' => [
                ['label' => 'Avaliar Ideias', 'rota' => '/ideias/analise'],
                ['label' => 'Projetos', 'rota' => '/projetos'],
            ],
            'notificacoes' => [
                'quantidade' => $notificacoesCount,
                'mensagem' => "Existem {$notificacoesCount} ideias pendentes de análise.",
            ],
        ]);
    }

    /**
     * Get home view payload for Lideranca profile.
     */
    public function lideranca(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'saudacao' => "Olá, {$user->name}!",
            'cardFixo' => [
                'titulo' => 'Resultados de Inovação',
                'descricao' => 'Acompanhe os principais indicadores de inovação e diretrizes estratégicas.',
            ],
            'botoesAcessoRapido' => [
                ['label' => 'Dashboard', 'rota' => '/dashboard'],
                ['label' => 'Projetos', 'rota' => '/projetos'],
                ['label' => 'Estratégias', 'rota' => '/estrategias'],
            ],
        ]);
    }
}
