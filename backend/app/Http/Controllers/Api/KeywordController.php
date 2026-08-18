<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKeywordRequest;
use App\Http\Resources\KeywordResource;
use App\Models\Project;
use App\Models\Keyword;

class KeywordController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $keywords = $project->keywords()->with('serpResults')->latest()->get();

        return KeywordResource::collection($keywords);
    }

    public function store(StoreKeywordRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $keyword = $project->keywords()->create(
            array_merge($request->validated(), ['serp_status' => 'pending'])
        );

        return new KeywordResource($keyword);
    }

    public function destroy(Project $project, Keyword $keyword)
    {
        $this->authorize('update', $project);

        $keyword->delete();

        return response()->json(['message' => 'Keyword deleted successfully']);
    }

    public function refresh(Project $project, Keyword $keyword)
    {
        $this->authorize('update', $project);

        $keyword->update(['serp_status' => 'pending']);

        return response()->json(['message' => 'Keyword queued for refresh']);
    }
}