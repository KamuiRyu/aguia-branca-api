<?php

namespace App\Http\Controllers;

use App\Http\Requests\EvaluateIdeaRequest;
use App\Http\Requests\StoreIdeaRequest;
use App\Http\Requests\UpdateIdeaStatusRequest;
use App\Http\Resources\IdeaResource;
use App\Models\Idea;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IdeaController extends Controller
{
    /**
     * Display a listing of the ideas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Idea::query();

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $ideas = $query->orderBy('id', 'desc')->get();

        return IdeaResource::collection($ideas);
    }

    /**
     * Store a newly created idea.
     */
    public function store(StoreIdeaRequest $request): IdeaResource
    {
        $user = $request->user();
        $validated = $request->validated();

        $idea = Idea::create([
            'title' => $validated['titulo'],
            'description' => $validated['descricao'],
            'category' => $validated['categoria'] ?? null,
            'sector' => $validated['setor'] ?? null,
            'expected_impact' => $validated['impactoEsperado'] ?? null,
            'urgency' => $validated['urgencia'] ?? null,
            'status' => 'PENDENTE_ANALISE',
            'user_id' => $user->id,
            'author_name' => $validated['autorNome'] ?? $user->name,
            'author_registration_id' => $validated['autorId'] ?? null,
        ]);

        return new IdeaResource($idea);
    }

    /**
     * Display the specified idea.
     */
    public function show(int $id): IdeaResource
    {
        $idea = Idea::findOrFail($id);

        return new IdeaResource($idea);
    }

    /**
     * List pending ideas for managers.
     */
    public function listPendingForManager(Request $request): AnonymousResourceCollection
    {
        $ideas = Idea::whereIn('status', ['PENDENTE_ANALISE', 'EM_ANALISE'])
            ->orderBy('id', 'desc')
            ->get();

        return IdeaResource::collection($ideas);
    }

    /**
     * Evaluate an idea (Approve / Reject).
     */
    public function evaluate(EvaluateIdeaRequest $request, int $id): IdeaResource
    {
        $user = $request->user();
        $validated = $request->validated();

        $idea = Idea::findOrFail($id);

        $idea->update([
            'status' => $validated['status'],
            'evaluation_notes' => $validated['observacao'] ?? null,
            'evaluated_by' => $user->id,
            'manager_responsible_name' => $user->name,
        ]);

        if ($validated['status'] === 'APROVADA') {
            $projectExists = Project::where('idea_origin_id', $idea->id)->exists();
            if (! $projectExists) {
                Project::create([
                    'name' => $idea->title,
                    'status' => 'PLANEJADO',
                    'idea_origin_id' => $idea->id,
                    'description' => $idea->description,
                ]);
            }
        }

        return new IdeaResource($idea);
    }

    /**
     * Directly update the status of an idea.
     */
    public function updateStatus(UpdateIdeaStatusRequest $request, int $id): IdeaResource
    {
        $validated = $request->validated();

        $idea = Idea::findOrFail($id);

        $idea->update([
            'status' => $validated['status'],
            'manager_responsible_name' => $validated['gestorResponsavel'],
        ]);

        if ($validated['status'] === 'APROVADA') {
            $projectExists = Project::where('idea_origin_id', $idea->id)->exists();
            if (! $projectExists) {
                Project::create([
                    'name' => $idea->title,
                    'status' => 'PLANEJADO',
                    'idea_origin_id' => $idea->id,
                    'description' => $idea->description,
                ]);
            }
        }

        return new IdeaResource($idea);
    }
}
