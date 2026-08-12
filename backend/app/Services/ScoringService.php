<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\CrawledPage;

/**
 * Runs once, after the queue for an audit is empty. Uses chunked queries
 * so it never loads all 5,000 pages into memory at once.
 */
class ScoringService
{
    public function score(Audit $audit): void
    {
        $total = 0;
        $technicalPoints = 0;
        $contentPoints = 0;
        $maxTechnicalPoints = 0;
        $maxContentPoints = 0;

        $titleCounts = [];
        $descCounts = [];

        CrawledPage::where('audit_id', $audit->id)
            ->chunkById(200, function ($pages) use (
                &$total, &$technicalPoints, &$contentPoints,
                &$maxTechnicalPoints, &$maxContentPoints,
                &$titleCounts, &$descCounts
            ) {
                foreach ($pages as $page) {
                    $total++;

                    // --- Technical checks (5 pts each) ---
                    $maxTechnicalPoints += 5 * 6;

                    $technicalPoints += $page->status_code === 200 ? 5 : 0;
                    $technicalPoints += $page->is_https ? 5 : 0;
                    $technicalPoints += $page->canonical_url ? 5 : 0;
                    $technicalPoints += $page->is_indexable ? 5 : 0;
                    $technicalPoints += $page->has_viewport ? 5 : 0;
                    $technicalPoints += ($page->response_time_ms && $page->response_time_ms < 1500) ? 5 : 0;

                    // --- Content checks (5 pts each) ---
                    $maxContentPoints += 5 * 5;

                    $technicalPoints += 0; // no-op, keeps structure explicit
                    $contentPoints += ($page->title && strlen($page->title) >= 10 && strlen($page->title) <= 60) ? 5 : 0;
                    $contentPoints += ($page->meta_description && strlen($page->meta_description) >= 50 && strlen($page->meta_description) <= 160) ? 5 : 0;
                    $contentPoints += (! empty($page->headings['h1']) && count($page->headings['h1']) === 1) ? 5 : 0;
                    $contentPoints += $page->word_count >= 300 ? 5 : 0;
                    $contentPoints += ($page->image_count === 0 || $page->images_missing_alt === 0) ? 5 : 0;

                    if ($page->title) {
                        $titleCounts[$page->title] = ($titleCounts[$page->title] ?? 0) + 1;
                    }
                    if ($page->meta_description) {
                        $descCounts[$page->meta_description] = ($descCounts[$page->meta_description] ?? 0) + 1;
                    }
                }
            });

        if ($total === 0) {
            $audit->update(['overall_score' => 0, 'technical_score' => 0, 'content_score' => 0]);

            return;
        }

        $duplicateTitles = collect($titleCounts)->filter(fn ($c) => $c > 1)->sum();
        $duplicateDescs = collect($descCounts)->filter(fn ($c) => $c > 1)->sum();

        // Duplicate-content penalty: up to 15 points off content score.
        $dupPenalty = min(15, ($duplicateTitles + $duplicateDescs) * 1.5);

        $technicalScore = $maxTechnicalPoints > 0 ? round(($technicalPoints / $maxTechnicalPoints) * 100, 2) : 0;
        $contentScore = $maxContentPoints > 0 ? max(0, round((($contentPoints / $maxContentPoints) * 100) - $dupPenalty, 2)) : 0;
        $overallScore = round(($technicalScore + $contentScore) / 2, 2);

        $audit->update([
            'overall_score' => $overallScore,
            'technical_score' => $technicalScore,
            'content_score' => $contentScore,
        ]);
    }
}
