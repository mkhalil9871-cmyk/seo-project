<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\Project;
use App\Models\UrlQueueItem;
use App\Services\SitemapParser;
use App\Services\UrlNormalizer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    // Portable severity ordering (works on SQLite locally and MySQL in production —
    // MySQL's FIELD() function isn't available on SQLite, so a CASE expression is used instead).
    private const SEVERITY_ORDER_SQL = "CASE severity
        WHEN 'critical' THEN 1
        WHEN 'high' THEN 2
        WHEN 'medium' THEN 3
        WHEN 'low' THEN 4
        ELSE 5 END";

    /**
     * Start a new audit for a project: creates the Audit row, seeds the queue with
     * the homepage AND (if reachable) every URL declared in the site's XML sitemap.
     * Everything else is discovered incrementally by crawler:process on later cron ticks.
     */
    public function start(Request $request, Project $project, SitemapParser $sitemapParser)
    {
        $this->authorizeProjectOwner($project);

        $existing = $project->audits()
            ->whereIn('status', [Audit::STATUS_QUEUED, Audit::STATUS_CRAWLING, Audit::STATUS_SCORING])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'An audit is already running for this project.',
                'audit_id' => $existing->id,
            ], 409);
        }

        $audit = $project->audits()->create(['status' => Audit::STATUS_QUEUED]);

        $homepage = UrlNormalizer::normalize($project->base_url);
        $seeded = [UrlNormalizer::hash($homepage) => [
            'audit_id' => $audit->id,
            'url' => $homepage,
            'url_hash' => UrlNormalizer::hash($homepage),
            'depth' => 0,
            'discovered_via' => 'link',
            'status' => UrlQueueItem::STATUS_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]];

        $sitemapUrlsFound = 0;
        try {
            $sitemapUrl = $sitemapParser->resolveSitemapUrl($project->sitemap_url, $project->base_url);
            $urls = $sitemapParser->fetchUrls($sitemapUrl);
            $sitemapUrlsFound = count($urls);

            foreach ($urls as $url) {
                if (count($seeded) >= $project->max_pages) {
                    break;
                }
                $normalized = UrlNormalizer::normalize($url);
                if (! UrlNormalizer::isSameHost($normalized, $project->base_url)) {
                    continue; // sitemap sometimes lists cross-domain assets, skip those
                }
                $hash = UrlNormalizer::hash($normalized);
                $seeded[$hash] ??= [
                    'audit_id' => $audit->id,
                    'url' => $normalized,
                    'url_hash' => $hash,
                    'depth' => 0,
                    'discovered_via' => 'sitemap',
                    'status' => UrlQueueItem::STATUS_PENDING,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        } catch (\Throwable $e) {
            report($e); // sitemap fetch failing shouldn't block starting the audit at all
        }

        foreach (array_chunk(array_values($seeded), 200) as $chunk) {
            UrlQueueItem::insertOrIgnore($chunk);
        }

        $audit->update(['sitemap_urls_found' => $sitemapUrlsFound]);

        return response()->json([
            'message' => 'Audit queued. It will begin processing on the next scheduled tick (within 1 minute).',
            'audit' => $audit,
            'seeded_urls' => count($seeded),
            'sitemap_urls_found' => $sitemapUrlsFound,
        ], 201);
    }

    public function status(Audit $audit)
    {
        $this->authorizeProjectOwner($audit->project);

        return response()->json([
            'id' => $audit->id,
            'status' => $audit->status,
            'pages_crawled' => $audit->pages_crawled,
            'pages_queued' => $audit->pages_queued,
            'pages_failed' => $audit->pages_failed,
            'sitemap_urls_found' => $audit->sitemap_urls_found,
            'overall_score' => $audit->overall_score,
            'technical_score' => $audit->technical_score,
            'content_score' => $audit->content_score,
            'comparison' => $audit->comparison_json,
            'started_at' => $audit->started_at,
            'finished_at' => $audit->finished_at,
            'error_message' => $audit->error_message,
        ]);
    }

    /**
     * GET /api/audits/{audit}/issues?severity=high&type=missing_title
     * The actual "here's what to fix" list — this is the product.
     */
    public function issues(Request $request, Audit $audit)
    {
        $this->authorizeProjectOwner($audit->project);

        $query = $audit->issues()->with('page:id,url,title');

        if ($severity = $request->query('severity')) {
            $query->where('severity', $severity);
        }
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        return response()->json(
            $query->orderByRaw(self::SEVERITY_ORDER_SQL)->paginate(50)
        );
    }

    /**
     * GET /api/audits/{audit}/issues/summary — counts grouped by type, for
     * a dashboard "12 issues found" style overview screen.
     */
    public function issuesSummary(Audit $audit)
    {
        $this->authorizeProjectOwner($audit->project);

        $summary = $audit->issues()
            ->selectRaw('type, severity, category, count(*) as count')
            ->groupBy('type', 'severity', 'category')
            ->orderByRaw(self::SEVERITY_ORDER_SQL)
            ->get();

        return response()->json($summary);
    }

    /**
     * GET /api/audits/{audit}/export.csv — downloadable issues report.
     * Streamed so a large issue list never has to sit fully in memory.
     */
    public function exportCsv(Audit $audit): StreamedResponse
    {
        $this->authorizeProjectOwner($audit->project);

        $filename = "audit-{$audit->id}-issues.csv";

        return response()->streamDownload(function () use ($audit) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Severity', 'Category', 'Issue Type', 'Page URL', 'Page Title', 'Detail']);

            $audit->issues()->with('page:id,url,title')
                ->orderByRaw(self::SEVERITY_ORDER_SQL)
                ->chunk(500, function ($issues) use ($out) {
                    foreach ($issues as $issue) {
                        fputcsv($out, [
                            $issue->severity,
                            $issue->category,
                            $issue->type,
                            $issue->page->url ?? '',
                            $issue->page->title ?? '',
                            $issue->detail,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * GET /api/audits/{audit}/export-pages.csv — one row per crawled page with every
     * captured data point (Screaming Frog's "Internal" tab equivalent).
     */
    public function exportPagesCsv(Audit $audit): StreamedResponse
    {
        $this->authorizeProjectOwner($audit->project);

        $filename = "audit-{$audit->id}-pages.csv";

        return response()->streamDownload(function () use ($audit) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'URL', 'Status Code', 'Redirect To', 'Indexable', 'Content Type', 'X-Robots-Tag',
                'Crawl Depth', 'Discovered Via', 'Response Time (ms)', 'HTML Size (bytes)',
                'Title', 'Title Length', 'Meta Description', 'Meta Description Length',
                'Meta Robots', 'Canonical URL', 'Canonical Status',
                'H1', 'H1 Count', 'H2 Count', 'H3 Count', 'H4 Count', 'H5 Count', 'H6 Count',
                'Word Count', 'Internal Links Out', 'External Links Out', 'Inbound Internal Links',
                'Image Count', 'Images Missing Alt', 'Has Structured Data', 'Hreflang Count',
                'Is HTTPS', 'Has Mixed Content', 'Has Viewport Meta', 'Language',
            ]);

            $audit->pages()
                ->orderBy('depth')->orderBy('id')
                ->chunk(500, function ($pages) use ($out) {
                    foreach ($pages as $page) {
                        $headings = $page->headings ?? [];
                        fputcsv($out, [
                            $page->url,
                            $page->status_code,
                            $page->redirect_to,
                            $page->is_indexable ? 'Yes' : 'No',
                            $page->content_type,
                            $page->x_robots_tag,
                            $page->depth,
                            $page->discovered_via,
                            $page->response_time_ms,
                            $page->html_size_bytes,
                            $page->title,
                            $page->title ? mb_strlen($page->title) : 0,
                            $page->meta_description,
                            $page->meta_description ? mb_strlen($page->meta_description) : 0,
                            $page->meta_robots,
                            $page->canonical_url,
                            $page->canonical_status,
                            implode(' | ', $headings['h1'] ?? []),
                            count($headings['h1'] ?? []),
                            count($headings['h2'] ?? []),
                            count($headings['h3'] ?? []),
                            count($headings['h4'] ?? []),
                            count($headings['h5'] ?? []),
                            count($headings['h6'] ?? []),
                            $page->word_count,
                            $page->internal_link_count,
                            $page->external_link_count,
                            $page->inbound_internal_links,
                            $page->image_count,
                            $page->images_missing_alt,
                            $page->has_schema ? 'Yes' : 'No',
                            count($page->hreflangs ?? []),
                            $page->is_https ? 'Yes' : 'No',
                            $page->has_mixed_content ? 'Yes' : 'No',
                            $page->has_viewport ? 'Yes' : 'No',
                            $page->lang,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Ownership check. Originally this was a manual `$project->user_id !== auth()->id()`
     * check, written before a Policy existed. The merge brought in a real `ProjectPolicy`
     * (used by ProjectController's view/update/delete), so this now defers to it instead —
     * one place decides "who owns this project", not two.
     */
    private function authorizeProjectOwner(Project $project): void
    {
        $this->authorize('view', $project);
    }
}
