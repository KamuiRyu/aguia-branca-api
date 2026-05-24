<?php

namespace App\Http\Controllers;

use App\Http\Resources\StrategyResource;
use App\Models\Strategy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StrategyController extends Controller
{
    /**
     * Display a listing of strategies.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $strategies = Strategy::orderBy('id', 'desc')->get();

        return StrategyResource::collection($strategies);
    }

    /**
     * Store a newly created strategy.
     */
    public function store(Request $request): StrategyResource
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string'],
            'ativa' => ['nullable', 'boolean'],
        ]);

        $strategy = Strategy::create([
            'title' => $validated['titulo'],
            'description' => $validated['descricao'],
            'active' => $validated['ativa'] ?? true,
        ]);

        return new StrategyResource($strategy);
    }

    /**
     * Update the specified strategy.
     */
    public function update(Request $request, int $id): StrategyResource
    {
        $validated = $request->validate([
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'descricao' => ['sometimes', 'required', 'string'],
            'ativa' => ['nullable', 'boolean'],
        ]);

        $strategy = Strategy::findOrFail($id);

        $updateData = [];
        if (isset($validated['titulo'])) {
            $updateData['title'] = $validated['titulo'];
        }
        if (isset($validated['descricao'])) {
            $updateData['description'] = $validated['descricao'];
        }
        if (array_key_exists('ativa', $validated)) {
            $updateData['active'] = $validated['ativa'];
        }

        $strategy->update($updateData);

        return new StrategyResource($strategy);
    }

    /**
     * Remove the specified strategy.
     */
    public function destroy(int $id): JsonResponse
    {
        $strategy = Strategy::findOrFail($id);
        $strategy->delete();

        return response()->json(null, 204);
    }
}
