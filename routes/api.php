<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ==========================================
// 1. ROTAS PÚBLICAS / AUTENTICAÇÃO
// ==========================================
Route::prefix('autenticacao')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/validar-token', [AuthController::class, 'validateToken']);
});

// ==========================================
// 2. ROTAS PROTEGIDAS (REQUEREM LOGIN)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // Perfil do Usuário Autenticado
    Route::get('/usuarios/me', [UserController::class, 'me']);

    // Home Views (Redirecionamento/Especificação por Perfil)
    Route::prefix('home')->group(function () {
        Route::get('/operador', [HomeController::class, 'operador'])->middleware('role:OPERADOR');
        Route::get('/gestor', [HomeController::class, 'gestor'])->middleware('role:GESTOR');
        Route::get('/lideranca', [HomeController::class, 'lideranca'])->middleware('role:LIDERANCA');
    });

    // Ideias - CRUD Geral e Fluxo de Avaliação
    Route::get('/ideias', [IdeaController::class, 'index']); // Suporta query param ?status=EM_ANALISE
    Route::post('/ideias', [IdeaController::class, 'store']); // Envio de ideias
    Route::get('/ideias/analise', [IdeaController::class, 'listPendingForManager'])->middleware('role:GESTOR');
    Route::get('/ideias/{id}', [IdeaController::class, 'show']);

    // Duas rotas de avaliação de status suportadas no escopo do projeto
    Route::patch('/ideias/{id}/avaliar', [IdeaController::class, 'evaluate'])->middleware('role:GESTOR'); // Avaliar (Aprovar/Reprovar com observação)
    Route::patch('/ideias/{id}/status', [IdeaController::class, 'updateStatus'])->middleware('role:GESTOR'); // Alterar status diretamente

    // Projetos
    Route::get('/projetos', [ProjectController::class, 'index']);
    Route::post('/projetos', [ProjectController::class, 'store'])->middleware('role:GESTOR,LIDERANCA');
    Route::get('/projetos/{id}', [ProjectController::class, 'show']);
    Route::put('/projetos/{id}', [ProjectController::class, 'update'])->middleware('role:GESTOR,LIDERANCA');

    // Estratégias
    Route::get('/estrategias', [StrategyController::class, 'index']);
    Route::post('/estrategias', [StrategyController::class, 'store'])->middleware('role:LIDERANCA');
    Route::put('/estrategias/{id}', [StrategyController::class, 'update'])->middleware('role:LIDERANCA');
    Route::delete('/estrategias/{id}', [StrategyController::class, 'destroy'])->middleware('role:LIDERANCA');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('role:LIDERANCA');
});
