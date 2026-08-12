<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\CrawledPage;
use App\Models\PageIssue;

/**
 * Runs in the same pass as ScoringService (right after the crawl queue for
 * an audit empties). Turns raw per-page fields into the actual list of
 * findings a user reads — "12 pages missing meta description" is the
 * product; a single 0-100 score number alone is not enough to compete with
 * tools like Semrush's Site Audit, which is fundamentally an issues list
 * with fix guidance, not just a grade.
 *
 * Site-wide checks (duplicates, orphans, redirect chains) are done with
 * separate grouped-query passes, since they can't be detected by looking
 * at one page in isolation.
 */
class IssueDetector
{
    public function detect(Audit $audit): void
    {
        PageIssue::where('audit_id', $audit->id)->delete();

        $this->detectPerPageIssues($audit);
        $this->detectSiteWideDuplicateTitlesDescriptions($audit);
        $this->detectDuplicateContent($audit);
        $this->detectOrphanPages($audit);
        $this->detectRedirectChains($audit);
        $this->detectBrokenLinks($audit);
        $this->detectCanonicalConflicts($audit);
        $this->detectSitemapDiff($audit);
    }

    protected function detectPerPageIssues(Audit $audit): void
    {
        $rows = [];

        CrawledPage::where('audit_id', $audit->id)
            ->chunkById(200, function ($pages) use ($audit, &$rows) {
                foreach ($pages as $page) {
                    foreach ($this->checkPage($page) as $issue) {
                        $rows[] = array_merge($issue, [
                            'audit_id' => $audit->id,
                            'crawled_page_id' => $page->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if (count($rows) >= 500) {
                    PageIssue::insert($rows);
                    $rows = [];
                }
            });

        if (! empty($rows)) {
            PageIssue::insert($rows);
        }
    }

    protected function checkPage(CrawledPage $page): array
    {
        $issues = [];

        if ($page->status_code && $page->status_code >= 500) {
            $issues[] = $this->issue('server_error', 'critical', 'technical', "HTTP {$page->status_code}");
        } elseif ($page->status_code && $page->status_code >= 400) {
            $issues[] = $this->issue('client_error', 'high', 'technical', "HTTP {$page->status_code}");
        }

        if (! $page->is_indexable && $page->status_code === 200) {
            $issues[] = $this->issue('noindex_on_live_page', 'critical', 'technical', 'meta robots contains noindex');
        }

        if (! $page->is_https) {
            $issues[] = $this->issue('not_https', 'critical', 'technical', null);
        }

        if ($page->has_mixed_content) {
            $issues[] = $this->issue('mixed_content', 'high', 'technical', 'HTTPS page loads at least one resource over HTTP');
        }

        if (! $page->title) {
            $issues[] = $this->issue('missing_title', 'high', 'on_page', null);
        } elseif (strlen($page->title) < 10) {
            $issues[] = $this->issue('title_too_short', 'medium', 'on_page', strlen($page->title) . ' chars');
        } elseif (strlen($page->title) > 60) {
            $issues[] = $this->issue('title_too_long', 'medium', 'on_page', strlen($page->title) . ' chars, may be truncated in SERPs');
        }

        $h1Count = count($page->headings['h1'] ?? []);
        if ($h1Count === 0) {
            $issues[] = $this->issue('missing_h1', 'high', 'on_page', null);
        } elseif ($h1Count > 1) {
            $issues[] = $this->issue('multiple_h1', 'medium', 'on_page', "{$h1Count} H1 tags found");
        }

        $presentLevels = [];
        foreach ([1, 2, 3, 4, 5, 6] as $level) {
            if (! empty($page->headings["h{$level}"] ?? [])) {
                $presentLevels[] = $level;
            }
        }
        for ($i = 1; $i < count($presentLevels); $i++) {
            if ($presentLevels[$i] - $presentLevels[$i - 1] > 1) {
                $issues[] = $this->issue('heading_hierarchy_skip', 'low', 'content',
                    "Jumps from H{$presentLevels[$i - 1]} to H{$presentLevels[$i]} with no heading level(s) in between");
                break; // one flag per page is enough signal, avoid noisy repeats
            }
        }

        if ($page->word_count > 0 && $page->word_count < 300) {
            $issues[] = $this->issue('thin_content', 'high', 'content', "{$page->word_count} words");
        }

        if (! $page->meta_description) {
            $issues[] = $this->issue('missing_meta_description', 'medium', 'on_page', null);
        } elseif (strlen($page->meta_description) < 50) {
            $issues[] = $this->issue('meta_description_too_short', 'low', 'on_page', strlen($page->meta_description) . ' chars');
        } elseif (strlen($page->meta_description) > 160) {
            $issues[] = $this->issue('meta_description_too_long', 'low', 'on_page', strlen($page->meta_description) . ' chars, may be truncated');
        }

        if (! $page->canonical_url) {
            $issues[] = $this->issue('missing_canonical', 'medium', 'technical', null);
        }

        if (! $page->has_viewport) {
            $issues[] = $this->issue('missing_viewport_meta', 'medium', 'technical', 'page may not be mobile-friendly');
        }

        if ($page->response_time_ms && $page->response_time_ms > 3000) {
            $issues[] = $this->issue('slow_response', 'medium', 'technical', "{$page->response_time_ms}ms");
        }

        if (! empty($page->hreflangs)) {
            $selfIncluded = collect($page->hreflangs)->contains(fn ($h) => ($h['url'] ?? null) === $page->url);
            if (! $selfIncluded) {
                $issues[] = $this->issue('hreflang_missing_self_reference', 'medium', 'technical',
                    'Page declares hreflang alternates but does not include a self-referencing entry');
            }
        }

        if (! $page->has_schema) {
            $issues[] = $this->issue('missing_structured_data', 'low', 'technical', null);
        }

        if ($page->image_count > 0 && $page->images_missing_alt > 0) {
            $severity = $page->images_missing_alt === $page->image_count ? 'medium' : 'low';
            $issues[] = $this->issue('missing_alt_text', $severity, 'content',
                "{$page->images_missing_alt} of {$page->image_count} images missing alt text");
        }

        return $issues;
    }

    protected function detectSiteWideDuplicateTitlesDescriptions(Audit $audit): void
    {
        // Grouping by the indexed sha256 hash instead of the raw varchar column keeps this
        // query fast (and index-safe on shared-hosting MySQL, where a full-length utf8mb4
        // varchar index can hit the key-length limit) as page counts grow.
        $duplicateTitleHashes = CrawledPage::where('audit_id', $audit->id)
            ->whereNotNull('title_hash')->select('title_hash')->groupBy('title_hash')
            ->havingRaw('count(*) > 1')->pluck('title_hash');

        foreach ($duplicateTitleHashes as $hash) {
            $pageIds = CrawledPage::where('audit_id', $audit->id)->where('title_hash', $hash)->pluck('id');
            $this->bulkInsertSameIssue($audit, $pageIds, 'duplicate_title', 'high', 'content',
                'Shared with ' . ($pageIds->count() - 1) . ' other page(s)');
        }

        $duplicateDescHashes = CrawledPage::where('audit_id', $audit->id)
            ->whereNotNull('meta_description_hash')->select('meta_description_hash')->groupBy('meta_description_hash')
            ->havingRaw('count(*) > 1')->pluck('meta_description_hash');

        foreach ($duplicateDescHashes as $hash) {
            $pageIds = CrawledPage::where('audit_id', $audit->id)->where('meta_description_hash', $hash)->pluck('id');
            $this->bulkInsertSameIssue($audit, $pageIds, 'duplicate_meta_description', 'medium', 'content',
                'Shared with ' . ($pageIds->count() - 1) . ' other page(s)');
        }
    }

    /**
     * Cross-checks each page's canonical target against what we now know about every page
     * in the finished crawl: does the canonical URL actually resolve to a 200? Is it itself
     * a redirect? Points off-site? CrawlerService already tagged the cheap self/cross_domain/
     * points_elsewhere verdict per-page at crawl time; this pass adds the part that needs the
     * *whole* crawl to be done — resolving what status code the target actually returned.
     */
    protected function detectCanonicalConflicts(Audit $audit): void
    {
        $pagesByNormalizedUrl = CrawledPage::where('audit_id', $audit->id)
            ->get(['id', 'url', 'status_code'])
            ->keyBy(fn ($p) => UrlNormalizer::normalize($p->url));

        CrawledPage::where('audit_id', $audit->id)
            ->whereNotNull('canonical_url')
            ->where('canonical_status', '!=', 'self')
            ->chunkById(200, function ($pages) use ($audit, $pagesByNormalizedUrl) {
                foreach ($pages as $page) {
                    if ($page->canonical_status === 'cross_domain') {
                        $this->insertOne($audit, $page->id, 'canonical_cross_domain', 'medium', 'technical',
                            "Canonical points to a different domain: {$page->canonical_url}");

                        continue;
                    }

                    $target = $pagesByNormalizedUrl->get(UrlNormalizer::normalize($page->canonical_url));

                    if (! $target) {
                        // Canonical points somewhere we never crawled — can't confirm it's valid.
                        continue;
                    }

                    if ($target->status_code && $target->status_code >= 300) {
                        $this->insertOne($audit, $page->id, 'canonical_points_to_non_200', 'high', 'technical',
                            "Canonical target ({$page->canonical_url}) returns HTTP {$target->status_code}");
                    }
                }
            });
    }

    /**
     * Compares the sitemap-seeded URLs against what actually got successfully crawled.
     * A URL sitting in the queue with discovered_via=sitemap that never reached 'done' status
     * means the sitemap is advertising a page to Google that's broken, blocked, or unreachable —
     * exactly the kind of "sitemap health" check Screaming Frog's list-mode/sitemap crawl does.
     */
    protected function detectSitemapDiff(Audit $audit): void
    {
        $uncrawled = \App\Models\UrlQueueItem::where('audit_id', $audit->id)
            ->where('discovered_via', 'sitemap')
            ->whereIn('status', [\App\Models\UrlQueueItem::STATUS_FAILED, \App\Models\UrlQueueItem::STATUS_SKIPPED])
            ->get();

        foreach ($uncrawled as $item) {
            PageIssue::create([
                'audit_id' => $audit->id,
                'crawled_page_id' => null,
                'type' => 'sitemap_url_not_crawlable',
                'severity' => 'medium',
                'category' => 'technical',
                'detail' => "In sitemap but never successfully crawled ({$item->url}): " . ($item->last_error ?? $item->status),
            ]);
        }

        $audit->update(['sitemap_urls_uncrawled' => $uncrawled->count()]);
    }

    protected function insertOne(Audit $audit, int $pageId, string $type, string $severity, string $category, ?string $detail): void
    {
        PageIssue::create([
            'audit_id' => $audit->id,
            'crawled_page_id' => $pageId,
            'type' => $type,
            'severity' => $severity,
            'category' => $category,
            'detail' => $detail,
        ]);
    }

    protected function detectDuplicateContent(Audit $audit): void
    {
        $duplicateHashes = CrawledPage::where('audit_id', $audit->id)
            ->whereNotNull('content_hash')->select('content_hash')->groupBy('content_hash')
            ->havingRaw('count(*) > 1')->pluck('content_hash');

        foreach ($duplicateHashes as $hash) {
            $pageIds = CrawledPage::where('audit_id', $audit->id)->where('content_hash', $hash)->pluck('id');
            $this->bulkInsertSameIssue($audit, $pageIds, 'duplicate_content', 'high', 'content',
                'Body content is identical (or near-identical) to ' . ($pageIds->count() - 1) . ' other page(s)');
        }
    }

    protected function detectOrphanPages(Audit $audit): void
    {
        CrawledPage::where('audit_id', $audit->id)
            ->where('discovered_via', 'sitemap')
            ->where('inbound_internal_links', 0)
            ->chunkById(200, function ($pages) use ($audit) {
                $pageIds = $pages->pluck('id');
                $this->bulkInsertSameIssue($audit, $pageIds, 'orphan_page', 'medium', 'technical',
                    'Found in sitemap but no internal link points to it');
            });
    }

    protected function detectRedirectChains(Audit $audit): void
    {
        $redirects = CrawledPage::where('audit_id', $audit->id)
            ->whereNotNull('redirect_to')->get(['id', 'url', 'redirect_to']);

        if ($redirects->isEmpty()) {
            return;
        }

        $byUrl = $redirects->keyBy(fn ($p) => UrlNormalizer::normalize($p->url));

        foreach ($redirects as $start) {
            $chain = [$start->url];
            $current = $start;
            $hops = 0;

            while ($current && $current->redirect_to && $hops < 10) {
                $nextNormalized = UrlNormalizer::normalize($current->redirect_to);
                $next = $byUrl->get($nextNormalized);
                if (! $next || in_array($next->url, $chain, true)) {
                    break;
                }
                $chain[] = $next->url;
                $current = $next;
                $hops++;
            }

            if ($hops >= 2) {
                PageIssue::create([
                    'audit_id' => $audit->id,
                    'crawled_page_id' => $start->id,
                    'type' => 'redirect_chain',
                    'severity' => 'medium',
                    'category' => 'technical',
                    'detail' => ($hops + 1) . '-hop chain: ' . implode(' -> ', $chain),
                ]);
            }
        }
    }

    protected function detectBrokenLinks(Audit $audit): void
    {
        $brokenTargets = CrawledPage::where('audit_id', $audit->id)
            ->where('status_code', '>=', 400)->pluck('url_hash')->toArray();

        if (empty($brokenTargets)) {
            return;
        }

        \App\Models\PageLink::where('audit_id', $audit->id)
            ->where('is_internal', true)
            ->whereIn('target_url_hash', $brokenTargets)
            ->chunkById(200, function ($links) use ($audit) {
                foreach ($links as $link) {
                    $sourcePage = CrawledPage::where('audit_id', $audit->id)
                        ->where('url_hash', UrlNormalizer::hash(UrlNormalizer::normalize($link->source_url)))
                        ->first();

                    if ($sourcePage) {
                        PageIssue::create([
                            'audit_id' => $audit->id,
                            'crawled_page_id' => $sourcePage->id,
                            'type' => 'broken_internal_link',
                            'severity' => 'high',
                            'category' => 'technical',
                            'detail' => "Links to {$link->target_url}",
                        ]);
                    }
                }
            });
    }

    protected function bulkInsertSameIssue(Audit $audit, $pageIds, string $type, string $severity, string $category, ?string $detail): void
    {
        $rows = $pageIds->map(fn ($id) => [
            'audit_id' => $audit->id,
            'crawled_page_id' => $id,
            'type' => $type,
            'severity' => $severity,
            'category' => $category,
            'detail' => $detail,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        foreach (array_chunk($rows, 200) as $chunk) {
            PageIssue::insert($chunk);
        }
    }

    protected function issue(string $type, string $severity, string $category, ?string $detail): array
    {
        return ['type' => $type, 'severity' => $severity, 'category' => $category, 'detail' => $detail];
    }
}
