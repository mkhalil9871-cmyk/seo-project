<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Keyword;
use App\Models\RankTracking;
use App\Services\RankTrackingService;
use Illuminate\Http\Request;

class RankTrackingController extends Controller
{
    protected RankTrackingService $service;

    public function __construct(RankTrackingService $service)
    {
        $this->service = $service;
    }

    // List rank history for a keyword
    public function index(Project $project, Keyword $keyword)
    {
        return $keyword->rankTrackings()->orderBy('checked_at', 'desc')->get();
    }

    // Trigger a manual rank check for a keyword
    public function check(Project $project, Keyword $keyword, Request $request)
    {
        $domain = $request->input('domain', $project->domain);

        $result = $this->service->checkPosition($keyword->keyword, $domain);

        if ($result === null) {
            return response()->json([
                'message' => 'No rank-tracking API key configured yet. Nothing checked.',
                'position' => null,
            ], 200);
        }

        $tracking = RankTracking::create([
            'keyword_id' => $keyword->id,
            'domain' => $domain,
            'position' => $result['position'] ?? null,
            'search_engine' => $result['search_engine'] ?? 'google',
            'checked_at' => now()->toDateString(),
        ]);

        return response()->json($tracking, 201);
    }
}