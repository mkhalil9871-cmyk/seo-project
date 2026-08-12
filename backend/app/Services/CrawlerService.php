<?php

namespace App\Services;

use App\DTOs\ParsedPage;
use App\Models\Audit;
use App\Models\CrawledPage;
use App\Models\PageLink;
use App\Models\UrlQueueItem;
use App\Services\Fetcher\PageFetcherInterface;
use App\Services\Parser\HtmlParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Processes ONE small batch of queued URLs for ONE audit, then returns.
 * Designed to be called every minute by cron (see routes/console.php),
 * never as a long-running process — this is what makes it safe on shared hosting.
 */
class CrawlerService
{
    public function __construct(
        private PageFetcherInterface $fetcher,
        private HtmlParser $parser,
        private RobotsTxtChecker $robots,
    ) {
    }

    public function processBatch(Audit $audit, int $batchSize = 10): void
    {
        $project = $audit->project;

        if ($audit->status === Audit::STATUS_QUEUED) {
            $audit->update(['status' => Audit::STATUS_CRAWLING, 'started_at' => now()]);
        }

        // Reclaim items stuck "processing" from a crashed/timed-out previous run (stale lock > 5 min).
        UrlQueueItem::where('audit_id', $audit->id)
            ->where('status', UrlQueueItem::STATUS_PROCESSING)
            ->where('locked_at', '<', now()->subMinutes(5))
            ->update(['status' => UrlQueueItem::STATUS_PENDING, 'locked_at' => null]);

        $batch = UrlQueueItem::where('audit_id', $audit->id)
            ->where('status', UrlQueueItem::STATUS_PENDING)
            ->orderBy('depth')
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        if ($batch->isEmpty()) {
            $this->finalizeIfComplete($audit);

            return;
        }

        // Lock this batch immediately so a second overlapping cron run won't grab the same rows.
        UrlQueueItem::whereIn('id', $batch->pluck('id'))
            ->update(['status' => UrlQueueItem::STATUS_PROCESSING, 'locked_at' => now()]);

        // Robots.txt checks are cheap/cached (RobotsTxtChecker caches per-host for 6h) and must
        // run before any request goes out, so do those first and split the batch.
        $toFetch = [];
        foreach ($batch as $item) {
            if ($project->respect_robots && ! $this->robots->isAllowed($item->url)) {
                $item->update(['status' => UrlQueueItem::STATUS_SKIPPED, 'last_error' => 'Disallowed by robots.txt']);
                continue;
            }
            $toFetch[] = $item;
        }

        if (! empty($toFetch)) {
            // The real speed win: fetch the whole remaining batch concurrently instead of one
            // request at a time. `crawl_delay_ms` from robots.txt is still respected — it's
            // applied as the pacing between pool waves, not as a per-request sleep, which is
            // why a "batch of 10" no longer takes 10x the round-trip time.
            $concurrency = min(
                (int) config('crawler.fetch_concurrency', 5),
                max(1, (int) ceil(1000 / max(1, $project->crawl_delay_ms)))
            );

            $urls = array_map(fn ($item) => $item->url, $toFetch);
            $fetchResults = $this->fetcher->fetchMany($urls, $concurrency);

            foreach ($toFetch as $item) {
                $result = $fetchResults[$item->url] ?? \App\DTOs\FetchResult::failure('No result returned from fetchMany');
                $this->processSingleUrl($audit, $project, $item, $result);
            }
        }

        $audit->update([
            'pages_crawled' => CrawledPage::where('audit_id', $audit->id)->count(),
            'pages_queued' => UrlQueueItem::where('audit_id', $audit->id)->where('status', UrlQueueItem::STATUS_PENDING)->count(),
            'pages_failed' => UrlQueueItem::where('audit_id', $audit->id)->where('status', UrlQueueItem::STATUS_FAILED)->count(),
        ]);

        $this->finalizeIfComplete($audit);
    }

    private function processSingleUrl(Audit $audit, $project, UrlQueueItem $item, \App\DTOs\FetchResult $result): void
    {
        try {
            if (! $result->success) {
                $this->markFailed($item, $result->errorMessage ?? 'Unknown fetch error');

                return;
            }

            $isRedirect = $result->statusCode >= 300 && $result->statusCode < 400;

            $parsed = ($result->body && ! $isRedirect)
                ? $this->parser->parse($result->body, $item->url)
                : new ParsedPage();

            $isHttps = str_starts_with(strtolower($item->url), 'https://');
            $mixedContent = $isHttps && collect($parsed->resourceUrls)->contains(fn ($u) => str_starts_with(strtolower($u), 'http://'));

            DB::transaction(function () use ($audit, $project, $item, $result, $parsed, $isRedirect, $isHttps, $mixedContent) {
                CrawledPage::updateOrCreate(
                    ['audit_id' => $audit->id, 'url_hash' => $item->url_hash],
                    [
                        'url' => $item->url,
                        'depth' => $item->depth,
                        'discovered_via' => $item->discovered_via,
                        'status_code' => $result->statusCode,
                        'redirect_to' => $result->redirectTo,
                        'response_time_ms' => $result->responseTimeMs,
                        'html_size_bytes' => $result->body ? strlen($result->body) : null,
                        'content_type' => $result->contentType,
                        'x_robots_tag' => $result->xRobotsTag,
                        'title' => $parsed->title ? mb_substr($parsed->title, 0, 500) : null,
                        'title_hash' => $parsed->title ? hash('sha256', mb_strtolower(trim($parsed->title))) : null,
                        'meta_description' => $parsed->metaDescription ? mb_substr($parsed->metaDescription, 0, 1000) : null,
                        'meta_description_hash' => $parsed->metaDescription ? hash('sha256', mb_strtolower(trim($parsed->metaDescription))) : null,
                        'meta_robots' => $parsed->metaRobots,
                        'canonical_url' => $parsed->canonicalUrl,
                        'canonical_status' => $this->canonicalStatus($parsed->canonicalUrl, $item->url, $project->base_url),
                        'charset' => $parsed->charset,
                        'lang' => $parsed->lang,
                        'has_viewport' => $parsed->hasViewport,
                        'headings' => $parsed->headings,
                        'word_count' => $parsed->wordCount,
                        'content_hash' => $parsed->bodyTextHash,
                        'internal_link_count' => collect($parsed->links)->filter(fn ($l) => UrlNormalizer::isSameHost($l['url'], $project->base_url))->count(),
                        'external_link_count' => collect($parsed->links)->filter(fn ($l) => ! UrlNormalizer::isSameHost($l['url'], $project->base_url))->count(),
                        'image_count' => $parsed->imageCount,
                        'images_missing_alt' => $parsed->imagesMissingAlt,
                        'json_ld' => $parsed->jsonLd,
                        'has_schema' => $parsed->hasSchema,
                        'hreflangs' => $parsed->hreflangs,
                        'rel_next' => $parsed->relNext,
                        'rel_prev' => $parsed->relPrev,
                        'is_https' => $isHttps,
                        'has_mixed_content' => $mixedContent,
                        'is_indexable' => $parsed->isIndexable(),
                    ]
                );

                $item->update(['status' => UrlQueueItem::STATUS_DONE, 'locked_at' => null]);

                if (! $isRedirect) {
                    $this->storeLinksAndDiscover($audit, $project, $item, $parsed);
                }
            });
        } catch (\Throwable $e) {
            Log::channel('crawler')->error('processSingleUrl failed', ['url' => $item->url, 'error' => $e->getMessage()]);
            $this->markFailed($item, $e->getMessage());
        }
    }

    private function storeLinksAndDiscover(Audit $audit, $project, UrlQueueItem $sourceItem, ParsedPage $parsed): void
    {
        if (empty($parsed->links)) {
            return;
        }

        $maxDepth = $project->max_depth;
        $maxPages = $project->max_pages;

        $alreadyQueuedCount = UrlQueueItem::where('audit_id', $audit->id)->count();

        $linkRows = [];
        $newQueueRows = [];
        $seenThisPage = [];

        foreach ($parsed->links as $link) {
            $normalized = UrlNormalizer::normalize($link['url']);
            $hash = UrlNormalizer::hash($normalized);
            $isInternal = UrlNormalizer::isSameHost($normalized, $project->base_url);

            if (isset($seenThisPage[$hash])) {
                continue;
            }
            $seenThisPage[$hash] = true;

            $linkRows[] = [
                'audit_id' => $audit->id,
                'source_url' => $sourceItem->url,
                'target_url' => $normalized,
                'target_url_hash' => $hash,
                'anchor_text' => mb_substr($link['anchor'] ?? '', 0, 500),
                'is_internal' => $isInternal,
                'is_nofollow' => $link['nofollow'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Only internal, non-nofollow links get queued for crawling.
            if (! $isInternal || ($link['nofollow'] ?? false)) {
                continue;
            }

            if ($sourceItem->depth + 1 > $maxDepth) {
                continue;
            }

            if ($alreadyQueuedCount + count($newQueueRows) >= $maxPages) {
                continue;
            }

            $newQueueRows[$hash] = [
                'audit_id' => $audit->id,
                'url' => $normalized,
                'url_hash' => $hash,
                'depth' => $sourceItem->depth + 1,
                'discovered_via' => 'link',
                'status' => UrlQueueItem::STATUS_PENDING,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($linkRows) {
            foreach (array_chunk($linkRows, 200) as $chunk) {
                PageLink::insert($chunk);
            }

            // Update inbound-link counters for internal targets we've already crawled.
            // (Targets not yet crawled will get their count reconciled by IssueDetector's
            // orphan-detection pass, which recomputes from page_links directly.)
            $internalTargetHashes = collect($linkRows)->where('is_internal', true)->pluck('target_url_hash')->unique();
            if ($internalTargetHashes->isNotEmpty()) {
                CrawledPage::where('audit_id', $audit->id)
                    ->whereIn('url_hash', $internalTargetHashes)
                    ->increment('inbound_internal_links');
            }
        }

        if ($newQueueRows) {
            // insertOrIgnore relies on the unique(audit_id, url_hash) constraint to dedupe
            // against URLs already queued/crawled — safe even with overlapping cron runs.
            foreach (array_chunk(array_values($newQueueRows), 200) as $chunk) {
                UrlQueueItem::insertOrIgnore($chunk);
            }
        }
    }

    /**
     * Cheap, per-page classification computed at crawl time. Doesn't need the full audit to be
     * done yet — it's a pure string/host comparison. Whether the canonical target actually
     * *resolves* to 200 is checked separately in IssueDetector::detectCanonicalConflicts(),
     * once every page in the audit has been crawled and we know every status code.
     */
    private function canonicalStatus(?string $canonicalUrl, string $pageUrl, string $baseUrl): ?string
    {
        if (! $canonicalUrl) {
            return null;
        }

        if (! UrlNormalizer::isSameHost($canonicalUrl, $baseUrl)) {
            return 'cross_domain';
        }

        return UrlNormalizer::normalize($canonicalUrl) === UrlNormalizer::normalize($pageUrl)
            ? 'self'
            : 'points_elsewhere';
    }

    private function markFailed(UrlQueueItem $item, string $error): void
    {
        $attempts = $item->attempts + 1;
        $maxAttempts = config('crawler.max_retry_attempts', 3);

        $item->update([
            'attempts' => $attempts,
            'last_error' => mb_substr($error, 0, 1000),
            'status' => $attempts >= $maxAttempts ? UrlQueueItem::STATUS_FAILED : UrlQueueItem::STATUS_PENDING,
            'locked_at' => null,
        ]);
    }

    private function finalizeIfComplete(Audit $audit): void
    {
        $pending = UrlQueueItem::where('audit_id', $audit->id)
            ->whereIn('status', [UrlQueueItem::STATUS_PENDING, UrlQueueItem::STATUS_PROCESSING])
            ->exists();

        if (! $pending && $audit->status === Audit::STATUS_CRAWLING) {
            $audit->update(['status' => Audit::STATUS_SCORING]);
            app(ScoringService::class)->score($audit);
            app(IssueDetector::class)->detect($audit);
            app(HistoryComparer::class)->compareToPrevious($audit);
            $audit->update(['status' => Audit::STATUS_COMPLETED, 'finished_at' => now()]);
        }
    }
}
