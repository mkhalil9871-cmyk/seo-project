<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateContentRequest;
use App\Http\Resources\ContentPieceResource;
use App\Models\Project;
use App\Models\ContentPiece;

class ContentPieceController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $pieces = $project->contentPieces()->latest()->get();

        return ContentPieceResource::collection($pieces);
    }

    public function generate(GenerateContentRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $piece = $project->contentPieces()->create([
            'keyword_id' => $request->validated('keyword_id'),
            'status' => 'pending',
        ]);

        return new ContentPieceResource($piece);
    }

    public function show(Project $project, ContentPiece $contentPiece)
    {
        $this->authorize('view', $project);

        return new ContentPieceResource($contentPiece);
    }
}