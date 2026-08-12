<?php

namespace App\Services;

use App\Models\Audit;

/**
 * Turns each audit from an isolated snapshot into part of a trend —
 * "12 new issues, 8 fixed since last time" — which is what makes repeat
 * audits actually useful instead of just a fresh disconnected report
 * every time. Result is stored back on the Audit as JSON so it's a single
 * cheap read for the dashboard, not a recomputation on every page view.
 */
class HistoryComparer
{
    public function compareToPrevious(Audit $audit): void
    {
        $previous = $audit->previousAudit();

        if (! $previous) {
            $audit->comparison_json = ['has_previous' => false];
            $audit->saveQuietly();

            return;
        }

        $currentCounts = $audit->issues()->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type');
        $previousCounts = $previous->issues()->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type');

        $allTypes = collect($currentCounts->keys())->merge($previousCounts->keys())->unique();

        $newIssueTypes = [];
        $fixedIssueTypes = [];
        $worsenedTypes = [];
        $improvedTypes = [];

        foreach ($allTypes as $type) {
            $cur = $currentCounts->get($type, 0);
            $prev = $previousCounts->get($type, 0);

            if ($prev === 0 && $cur > 0) {
                $newIssueTypes[] = ['type' => $type, 'count' => $cur];
            } elseif ($prev > 0 && $cur === 0) {
                $fixedIssueTypes[] = ['type' => $type, 'count' => $prev];
            } elseif ($cur > $prev) {
                $worsenedTypes[] = ['type' => $type, 'from' => $prev, 'to' => $cur];
            } elseif ($cur < $prev) {
                $improvedTypes[] = ['type' => $type, 'from' => $prev, 'to' => $cur];
            }
        }

        $audit->comparison_json = [
            'has_previous' => true,
            'previous_audit_id' => $previous->id,
            'previous_score' => $previous->overall_score,
            'score_delta' => $audit->overall_score !== null && $previous->overall_score !== null
                ? round($audit->overall_score - $previous->overall_score, 2)
                : null,
            'total_issues_now' => $currentCounts->sum(),
            'total_issues_before' => $previousCounts->sum(),
            'new_issue_types' => $newIssueTypes,
            'fixed_issue_types' => $fixedIssueTypes,
            'worsened_issue_types' => $worsenedTypes,
            'improved_issue_types' => $improvedTypes,
        ];
        $audit->saveQuietly();
    }
}
