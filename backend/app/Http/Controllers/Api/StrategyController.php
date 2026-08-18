<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateStrategyRequest;
use App\Http\Resources\StrategyResource;
use App\Models\Project;
use App\Models\Strategy;

class StrategyController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $strategies = $project->strategies()->latest()->get();

        return StrategyResource::collection($strategies);
    }

    public function generate(GenerateStrategyRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $strategy = $project->strategies()->create([
            'content' => [],
            'status' => 'pending',
        ]);

        return new StrategyResource($strategy);
    }

    public function show(Project $project, Strategy $strategy)
    {
        $this->authorize('view', $project);

        return new StrategyResource($strategy);
    }
}