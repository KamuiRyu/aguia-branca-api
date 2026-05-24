<?php

use App\Models\Idea;
use App\Models\Project;
use App\Models\Strategy;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

test('login com credenciais validas retorna token', function () {
    $user = User::factory()->create([
        'email' => 'operador@email.com',
        'password' => bcrypt('senha123'),
        'profile' => 'OPERADOR',
    ]);

    $response = $this->postJson('/api/autenticacao/login', [
        'email' => 'operador@email.com',
        'password' => 'senha123',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'mensagem',
            'token',
            'usuario' => ['id', 'nome', 'email', 'perfil'],
        ])
        ->assertJsonPath('usuario.perfil', 'OPERADOR');
});

test('login com credenciais invalidas falha', function () {
    $response = $this->postJson('/api/autenticacao/login', [
        'email' => 'inexistente@email.com',
        'password' => 'senhaErrada',
    ]);

    $response->assertStatus(401);
});

test('validar token valido retorna sucesso e dados do usuario', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->postJson('/api/autenticacao/validar-token', [
        'token' => $token,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'valido' => true,
            'usuario' => [
                'id' => $user->id,
                'nome' => $user->name,
                'email' => $user->email,
                'perfil' => $user->profile,
            ],
        ]);
});

test('validar token invalido falha', function () {
    $response = $this->postJson('/api/autenticacao/validar-token', [
        'token' => 'token-invalido',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'valido' => false,
        ]);
});

test('obter dados do usuario logado', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/usuarios/me');

    $response->assertSuccessful()
        ->assertJson([
            'id' => $user->id,
            'nome' => $user->name,
            'email' => $user->email,
            'perfil' => $user->profile,
        ]);
});

test('controle de perfil do middleware CheckProfile', function () {
    $operador = User::factory()->create(['profile' => 'OPERADOR']);
    $gestor = User::factory()->gestor()->create();
    $lideranca = User::factory()->lideranca()->create();

    Sanctum::actingAs($operador);
    $this->getJson('/api/home/gestor')->assertForbidden();
    $this->getJson('/api/home/lideranca')->assertForbidden();

    Sanctum::actingAs($gestor);
    $this->getJson('/api/home/gestor')->assertSuccessful();
    $this->getJson('/api/home/lideranca')->assertForbidden();

    Sanctum::actingAs($lideranca);
    $this->getJson('/api/home/lideranca')->assertSuccessful();
    $this->getJson('/api/home/gestor')->assertForbidden();
});

test('rotas da home por perfil', function () {
    $operador = User::factory()->create(['profile' => 'OPERADOR']);
    $gestor = User::factory()->gestor()->create();
    $lideranca = User::factory()->lideranca()->create();

    Sanctum::actingAs($operador);
    $this->getJson('/api/home/operador')
        ->assertSuccessful()
        ->assertJsonStructure(['saudacao', 'cardFixo', 'botoesAcessoRapido']);

    Sanctum::actingAs($gestor);
    Idea::factory()->create(['status' => 'PENDENTE_ANALISE']);
    Idea::factory()->create(['status' => 'EM_ANALISE']);

    $this->getJson('/api/home/gestor')
        ->assertSuccessful()
        ->assertJsonStructure(['saudacao', 'cardFixo', 'botoesAcessoRapido', 'notificacoes'])
        ->assertJsonPath('notificacoes.quantidade', 2);

    Sanctum::actingAs($lideranca);
    $this->getJson('/api/home/lideranca')
        ->assertSuccessful()
        ->assertJsonStructure(['saudacao', 'cardFixo', 'botoesAcessoRapido']);
});

test('operador pode cadastrar ideia', function () {
    $user = User::factory()->create(['profile' => 'OPERADOR']);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/ideias', [
        'titulo' => 'Nova Ideia Sustentavel',
        'descricao' => 'Detalhes da nova ideia',
        'categoria' => 'SUSTENTABILIDADE',
        'setor' => 'Operações',
        'impactoEsperado' => 'Redução de poluentes',
        'urgencia' => 'Alta',
        'autorNome' => 'João Operador',
        'autorId' => 'OP-123',
    ]);

    $response->assertSuccessful();
    $this->assertDatabaseHas('ideas', [
        'title' => 'Nova Ideia Sustentavel',
        'description' => 'Detalhes da nova ideia',
        'user_id' => $user->id,
        'status' => 'PENDENTE_ANALISE',
    ]);
});

test('filtrar e listar ideias', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Idea::factory()->create(['status' => 'PENDENTE_ANALISE']);
    Idea::factory()->create(['status' => 'EM_ANALISE']);

    $response = $this->getJson('/api/ideias');
    $response->assertSuccessful();
    expect($response->json())->toHaveCount(2);

    $responseFiltered = $this->getJson('/api/ideias?status=EM_ANALISE');
    $responseFiltered->assertSuccessful();
    expect($responseFiltered->json())->toHaveCount(1);
    expect($responseFiltered->json('0.status'))->toBe('EM_ANALISE');
});

test('gestor pode listar pendentes e avaliar ideia criando projeto ao aprovar', function () {
    $gestor = User::factory()->gestor()->create();
    $idea = Idea::factory()->create(['status' => 'PENDENTE_ANALISE', 'title' => 'Ideia a aprovar']);

    Sanctum::actingAs($gestor);

    $this->getJson('/api/ideias/analise')
        ->assertSuccessful()
        ->assertJsonCount(1);

    $response = $this->patchJson("/api/ideias/{$idea->id}/avaliar", [
        'status' => 'APROVADA',
        'observacao' => 'Boa ideia, aprovada!',
    ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('ideas', [
        'id' => $idea->id,
        'status' => 'APROVADA',
        'evaluated_by' => $gestor->id,
        'manager_responsible_name' => $gestor->name,
        'evaluation_notes' => 'Boa ideia, aprovada!',
    ]);

    $this->assertDatabaseHas('projects', [
        'name' => 'Ideia a aprovar',
        'status' => 'PLANEJADO',
        'idea_origin_id' => $idea->id,
    ]);
});

test('gestor pode atualizar status diretamente e criar projeto se aprovar', function () {
    $gestor = User::factory()->gestor()->create();
    $idea = Idea::factory()->create(['status' => 'EM_ANALISE', 'title' => 'Ideia status direto']);

    Sanctum::actingAs($gestor);

    $response = $this->patchJson("/api/ideias/{$idea->id}/status", [
        'status' => 'APROVADA',
        'gestorResponsavel' => 'Gestor Silva',
    ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('ideas', [
        'id' => $idea->id,
        'status' => 'APROVADA',
        'manager_responsible_name' => 'Gestor Silva',
    ]);

    $this->assertDatabaseHas('projects', [
        'name' => 'Ideia status direto',
        'status' => 'PLANEJADO',
        'idea_origin_id' => $idea->id,
    ]);
});

test('CRUD de projetos', function () {
    $lideranca = User::factory()->lideranca()->create();
    $idea = Idea::factory()->create();

    Sanctum::actingAs($lideranca);

    $responseStore = $this->postJson('/api/projetos', [
        'nome' => 'Projeto Especial',
        'status' => 'PLANEJADO',
        'ideiaOrigemId' => $idea->id,
        'equipeResponsavel' => 'Time Alpha',
        'orcamento' => 50000,
        'prazo' => '2026-12-31',
        'percentualProgresso' => 10,
        'roiEsperado' => 15.5,
        'descricao' => 'Descricao do projeto especial',
    ]);

    $responseStore->assertSuccessful();
    $projectId = $responseStore->json('id');

    $this->getJson('/api/projetos')
        ->assertSuccessful()
        ->assertJsonCount(1);

    $this->getJson("/api/projetos/{$projectId}")
        ->assertSuccessful()
        ->assertJsonPath('titulo', 'Projeto Especial')
        ->assertJsonPath('responsavel', 'Time Alpha')
        ->assertJsonPath('investimento', 50000.0);

    $this->patchJson("/api/projetos/{$projectId}", [
        'nome' => 'Projeto Especial Atualizado',
        'status' => 'EM_ANDAMENTO',
        'percentualProgresso' => 25,
    ])->assertSuccessful();

    $this->assertDatabaseHas('projects', [
        'id' => $projectId,
        'name' => 'Projeto Especial Atualizado',
        'status' => 'EM_ANDAMENTO',
        'progress_percentage' => 25,
    ]);
});

test('CRUD de estrategias', function () {
    $lideranca = User::factory()->lideranca()->create();
    Sanctum::actingAs($lideranca);

    $responseStore = $this->postJson('/api/estrategias', [
        'titulo' => 'Reducao de Carbono',
        'descricao' => 'Meta de carbono zero',
        'ativa' => true,
    ]);

    $responseStore->assertSuccessful();
    $strategyId = $responseStore->json('id');

    $this->getJson('/api/estrategias')
        ->assertSuccessful()
        ->assertJsonCount(1);

    $this->putJson("/api/estrategias/{$strategyId}", [
        'titulo' => 'Reducao de Carbono 2030',
        'ativa' => false,
    ])->assertSuccessful();

    $this->assertDatabaseHas('strategies', [
        'id' => $strategyId,
        'title' => 'Reducao de Carbono 2030',
        'active' => false,
    ]);

    $this->deleteJson("/api/estrategias/{$strategyId}")
        ->assertStatus(204);

    expect(Strategy::find($strategyId))->toBeNull();
});

test('dashboard calcula estatisticas corretas', function () {
    $lideranca = User::factory()->lideranca()->create();
    Sanctum::actingAs($lideranca);

    Idea::factory()->create(['status' => 'APROVADA']);
    Idea::factory()->create(['status' => 'APROVADA']);
    Idea::factory()->create(['status' => 'PENDENTE_ANALISE']);
    Idea::factory()->create(['status' => 'REPROVADA']);

    Project::factory()->create(['status' => 'EM_ANDAMENTO']);
    Project::factory()->create(['status' => 'CONCLUIDO']);

    $response = $this->getJson('/api/dashboard');

    $response->assertSuccessful()
        ->assertJson([
            'totalIdeias' => 4,
            'ideiasAprovadas' => 2,
            'ideiasPendentes' => 1,
            'projetosAtivos' => 1,
            'projetosConcluidos' => 1,
            'indicadores' => [
                [
                    'nome' => 'Taxa de aprovação',
                    'valor' => '50%',
                ],
            ],
        ]);
});

test('dashboard com zero ideias nao causa divisao por zero', function () {
    $lideranca = User::factory()->lideranca()->create();
    Sanctum::actingAs($lideranca);

    $response = $this->getJson('/api/dashboard');

    $response->assertSuccessful()
        ->assertJson([
            'totalIdeias' => 0,
            'ideiasAprovadas' => 0,
            'ideiasPendentes' => 0,
            'projetosAtivos' => 0,
            'projetosConcluidos' => 0,
            'indicadores' => [
                [
                    'nome' => 'Taxa de aprovação',
                    'valor' => '0%',
                ],
            ],
        ]);
});

test('requisicao nao autenticada na api retorna 401 json ao inves de redirecionar para rota login', function () {
    $response = $this->get('/api/usuarios/me');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});
