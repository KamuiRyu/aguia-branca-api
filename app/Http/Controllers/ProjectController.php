<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = Project::orderBy('id', 'desc')->get();

        return ProjectResource::collection($projects);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request): ProjectResource
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:PLANEJADO,EM_ANDAMENTO,CONCLUIDO,CANCELADO'],
            'ideiaOrigemId' => ['required', 'exists:ideas,id'],
            'equipeResponsavel' => ['nullable', 'string', 'max:255'],
            'orcamento' => ['nullable', 'numeric'],
            'prazo' => ['nullable', 'date'],
            'percentualProgresso' => ['nullable', 'integer', 'between:0,100'],
            'roiEsperado' => ['nullable', 'numeric'],
            'descricao' => ['nullable', 'string'],
        ]);

        $project = Project::create([
            'name' => $validated['nome'],
            'status' => $validated['status'],
            'idea_origin_id' => $validated['ideiaOrigemId'],
            'responsible_team' => $validated['equipeResponsavel'] ?? null,
            'budget' => $validated['orcamento'] ?? null,
            'deadline' => $validated['prazo'] ?? null,
            'progress_percentage' => $validated['percentualProgresso'] ?? 0,
            'expected_roi' => $validated['roiEsperado'] ?? null,
            'description' => $validated['descricao'] ?? null,
        ]);

        return new ProjectResource($project);
    }

    /**
     * Display the specified project.
     */
    public function show(int $id): ProjectResource
    {
        $project = Project::findOrFail($id);

        return new ProjectResource($project);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, int $id): ProjectResource
    {
        $validated = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'in:PLANEJADO,EM_ANDAMENTO,CONCLUIDO,CANCELADO'],
            'ideiaOrigemId' => ['sometimes', 'required', 'exists:ideas,id'],
            'equipeResponsavel' => ['nullable', 'string', 'max:255'],
            'orcamento' => ['nullable', 'numeric'],
            'prazo' => ['nullable', 'date'],
            'percentualProgresso' => ['nullable', 'integer', 'between:0,100'],
            'roiEsperado' => ['nullable', 'numeric'],
            'descricao' => ['nullable', 'string'],
        ]);

        $project = Project::findOrFail($id);

        $updateData = [];
        if (isset($validated['nome'])) {
            $updateData['name'] = $validated['nome'];
        }
        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }
        if (isset($validated['ideiaOrigemId'])) {
            $updateData['idea_origin_id'] = $validated['ideiaOrigemId'];
        }
        if (array_key_exists('equipeResponsavel', $validated)) {
            $updateData['responsible_team'] = $validated['equipeResponsavel'];
        }
        if (array_key_exists('orcamento', $validated)) {
            $updateData['budget'] = $validated['orcamento'];
        }
        if (array_key_exists('prazo', $validated)) {
            $updateData['deadline'] = $validated['prazo'];
        }
        if (array_key_exists('percentualProgresso', $validated)) {
            $updateData['progress_percentage'] = $validated['percentualProgresso'] ?? 0;
        }
        if (array_key_exists('roiEsperado', $validated)) {
            $updateData['expected_roi'] = $validated['roiEsperado'];
        }
        if (array_key_exists('descricao', $validated)) {
            $updateData['description'] = $validated['descricao'];
        }

        $project->update($updateData);

        return new ProjectResource($project);
    }
}
