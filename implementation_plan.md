# Plano de Implementação: API do Sistema Águia Branca (Laravel)

Este plano descreve a arquitetura, o esquema do banco de dados, as rotas e os componentes necessários para construir a API REST do Sistema Águia Branca utilizando o framework Laravel.

---

## 1. Visão Geral do Sistema

O Sistema Águia Branca gerencia o fluxo de inovação da empresa, passando pelas etapas de envio de ideias por **Operadores**, avaliação por **Gestores**, e acompanhamento de indicadores e projetos por parte da **Liderança**.

A API será construída em **Laravel 13**, expondo endpoints JSON e utilizando autenticação baseada em tokens com o **Laravel Sanctum**.

---

## 2. Modelagem de Banco de Dados (Migrations e Models)

Precisamos definir e ajustar as entidades no banco de dados para suportar todas as funcionalidades.

### 2.1. Usuários (Alteração da tabela padrão `users`)
Em vez de criar uma tabela do zero, adicionaremos os campos necessários à tabela `users` padrão do Laravel por meio de uma migration de alteração:

```php
Schema::table('users', function (Blueprint $table) {
    $table->enum('profile', ['OPERADOR', 'GESTOR', 'LIDERANCA'])->after('password');
});
```
*(Nota: O campo `registration_number` foi removido conforme solicitado)*.

### 2.2. Estratégias da Empresa (`strategies`)
Armazena os direcionamentos estratégicos criados pela liderança.

```php
Schema::create('strategies', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description');
    $table->boolean('active')->default(true);
    $table->timestamps();
});
```

### 2.3. Ideias (`ideas`)
Armazena as ideias cadastradas pelos operadores.

```php
Schema::create('ideas', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description');
    $table->string('category')->nullable(); // Ex: SUSTENTABILIDADE
    $table->string('sector')->nullable(); // Ex: Administrativo
    $table->string('expected_impact')->nullable(); // Ex: Redução de Custos
    $table->string('urgency')->nullable(); // Ex: Média, Alta, Baixa
    $table->enum('status', ['PENDENTE_ANALISE', 'EM_ANALISE', 'APROVADA', 'REPROVADA', 'EM_PROJETO'])->default('PENDENTE_ANALISE');
    
    // Relação com Usuário (Autor)
    $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->string('author_name')->nullable(); // Guardado para fins históricos/formulários
    $table->string('author_registration_id')->nullable(); // Ex: OP-1024
    
    // Relação com Gestor (Avaliador)
    $table->foreignId('evaluated_by')->nullable()->constrained('users')->onDelete('set null');
    $table->string('manager_responsible_name')->nullable(); // Nome do gestor que avaliou
    $table->text('evaluation_notes')->nullable(); // Observação da avaliação
    
    $table->timestamps();
});
```

### 2.4. Projetos (`projects`)
Armazena os projetos originados a partir de ideias aprovadas.

```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->enum('status', ['PLANEJADO', 'EM_ANDAMENTO', 'CONCLUIDO', 'CANCELADO'])->default('PLANEJADO');
    $table->foreignId('idea_origin_id')->constrained('ideas')->onDelete('cascade');
    
    // Dados adicionais mostrados no mock visual
    $table->string('responsible_team')->nullable(); // Ex: Equipe de TI
    $table->decimal('budget', 15, 2)->nullable(); // Ex: 45000.00
    $table->date('deadline')->nullable();
    $table->integer('progress_percentage')->default(0); // Ex: 60 (para 60%)
    $table->decimal('expected_roi', 5, 2)->nullable(); // Ex: 25.00 (para 25%)
    $table->text('description')->nullable();
    $table->timestamps();
});
```

---

## 3. Estrutura de Rotas (`routes/api.php`)

As rotas serão divididas entre públicas (autenticação básica) e protegidas (requerem token de autenticação e, em alguns casos, perfil de acesso específico).

```php
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
```

---

## 4. Middleware de Autorização por Perfil (`RoleMiddleware`)

Para garantir que apenas os perfis autorizados acessem endpoints específicos, criaremos um middleware `CheckProfile` (ou `RoleMiddleware`):

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProfile
{
    public function handle(Request $request, Closure $next, ...$profiles): Response
    {
        $user = $request->user();
        
        if (!$user || !in_array($user->profile, $profiles)) {
            return response()->json([
                'mensagem' => 'Acesso não autorizado para o seu perfil.'
            ], 403);
        }

        return $next($request);
    }
}
```

---

## 5. Implementação dos Controllers

### 5.1. `AuthController`
Gerencia a validação de credenciais e de tokens de acesso.
Para a validação stateless exata do token enviado via corpo JSON (como exigido no splash):
```php
public function validateToken(Request $request)
{
    $request->validate(['token' => 'required|string']);
    
    // No Laravel Sanctum, buscamos o token pessoal de acesso
    $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->token);
    
    if (!$token || $token->wasAbused()) {
        return response()->json(['valido' => false], 401);
    }
    
    $user = $token->tokenable;
    
    return response()->json([
        'valido' => true,
        'usuario' => [
            'id' => $user->id,
            'nome' => $user->name,
            'email' => $user->email,
            'perfil' => $user->profile
        ]
    ]);
}
```

E no login:
```php
public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string'
    ]);
    
    $user = User::where('email', $credentials['email'])->first();
                
    if (!$user || !\Hash::check($credentials['password'], $user->password)) {
        return response()->json(['mensagem' => 'Credenciais inválidas'], 401);
    }
    
    $token = $user->createToken('auth_token')->plainTextToken;
    
    return response()->json([
        'mensagem' => 'Login realizado com sucesso',
        'token' => $token,
        'usuario' => [
            'id' => $user->id,
            'nome' => $user->name,
            'email' => $user->email,
            'perfil' => $user->profile
        ]
    ]);
}
```

### 5.2. `HomeController`
Gera os payloads ricos do dashboard inicial baseados no perfil do usuário conectado.

- **Operador**: Saudação, card fixo, e botões de acesso rápido.
- **Gestor**: Saudação, card fixo de análise de ideias, botões de acesso rápido e contador de notificações de ideias com status `PENDENTE_ANALISE` ou `EM_ANALISE`.
- **Liderança**: Saudação, card de resultados, e botões para dashboard, projetos e estratégias.

### 5.3. `IdeaController`
Trata do fluxo de ideias.
- `index`: Lista ideias com filtros dinâmicos na URL (`?status=EM_ANALISE`, `?status=PENDENTE_ANALISE`, etc.).
- `store`: Permite que o operador envie novas ideias. Preenche automaticamente campos padrões de auditoria, como `autor_id` e `autor_nome` usando o usuário logado se não forem explicitamente fornecidos no body.
- `evaluate` e `updateStatus`: Permite alterar o status da ideia, atualizar observações de avaliação e, no caso de aprovação (`APROVADA`), criará automaticamente um registro correspondente na tabela `projects` com status inicial `PLANEJADO` apontando para a ideia de origem.

### 5.4. `DashboardController`
Calcula as métricas solicitadas pela Liderança:
- `totalIdeias` (Count geral da tabela `ideas`).
- `ideiasAprovadas` (Count da tabela `ideas` onde `status = APROVADA`).
- `ideiasPendentes` (Count da tabela `ideas` onde `status = PENDENTE_ANALISE` ou `EM_ANALISE`).
- `projetosAtivos` (Count da tabela `projects` onde `status = EM_ANDAMENTO`).
- `projetosConcluidos` (Count da tabela `projects` onde `status = CONCLUIDO`).
- `indicadores`: Array com a `Taxa de aprovação` calculada como `(ideiasAprovadas / totalIdeias) * 100`.

---

## 6. Form Requests & API Resources

Utilizaremos o padrão nativo do Laravel para validação de entrada (`Form Requests`) e transformação de saída JSON (`API Resources`), garantindo acoplamento fraco e padronização com a assinatura esperada pelo app cliente.

### 6.1. Form Requests (Validação)
Para encapsular as regras de negócio de validação:

* **`LoginRequest`**:
  * `email`: `required|email`
  * `password`: `required|string`
* **`StoreIdeaRequest`**:
  * `titulo`: `required|string|max:255`
  * `descricao`: `required|string`
  * `categoria`: `nullable|string` (Ex: SUSTENTABILIDADE)
  * `setor`: `nullable|string`
  * `impactoEsperado`: `nullable|string`
  * `urgencia`: `nullable|string`
  * `autorNome`: `nullable|string`
  * `autorId`: `nullable|string`
* **`EvaluateIdeaRequest`**:
  * `status`: `required|in:APROVADA,REPROVADA`
  * `observacao`: `nullable|string|max:1000`
* **`UpdateIdeaStatusRequest`**:
  * `status`: `required|in:PENDENTE_ANALISE,EM_ANALISE,APROVADA,REPROVADA,EM_PROJETO`
  * `gestorResponsavel`: `required|string`

### 6.2. API Resources (Transformação JSON)
Para mapear os atributos snake_case do banco de dados para o camelCase/formato exigido na resposta das APIs:

* **`UserResource`**:
  Retorna as informações do perfil do usuário logado:
  ```json
  {
    "id": 1,
    "nome": "João Silva",
    "email": "joao@email.com",
    "perfil": "OPERADOR"
  }
  ```
* **`IdeaResource`**:
  Formata a saída de listagens e detalhes de ideias, mapeando campos como `author_name` para `autorNome` e `created_at` para `dataCriacao` / `criadoEm`:
  ```json
  {
    "id": 10,
    "titulo": "Reduzir desperdício de papel",
    "descricao": "Digitalizar processos internos.",
    "autor": "João Silva",
    "autorNome": "João Silva",
    "autorId": "OP-1024",
    "categoria": "SUSTENTABILIDADE",
    "status": "PENDENTE_ANALISE",
    "dataCriacao": "2026-05-24T10:00:00"
  }
  ```
* **`ProjectResource`**:
  Retorna dados de projetos, formatando o relacionamento com a ideia de origem:
  ```json
  {
    "id": 1,
    "nome": "Projeto Papel Zero",
    "status": "EM_ANDAMENTO",
    "ideiaOrigemId": 10
  }
  ```
* **`StrategyResource`**:
  Mapeia as diretrizes estratégicas da empresa:
  ```json
  {
    "id": 1,
    "titulo": "Inovação sustentável",
    "descricao": "Promover ideias voltadas à sustentabilidade.",
    "ativa": true
  }
  ```


