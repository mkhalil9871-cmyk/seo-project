<?php

namespace App\Console\Commands;

use App\Models\Audit;
use App\Services\CrawlerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * php artisan crawler:process
 *
 * Runs to completion FAST (typically 1-5 seconds for a 10-page batch) and exits.
 * Cron calls this every minute. It processes a small batch for EVERY audit that's
 * currently crawling, so multiple audits progress concurrently without needing
 * a persistent worker process.
 */
class ProcessCrawlQueue extends Command
{
    protected $signature = 'crawler:process {--audit= : Process only this audit ID} {--batch-size=10}';

    protected $description = 'Process one small batch of the crawl queue for each active audit (safe for cron on shared hosting)';

    public function handle(CrawlerService $crawler): int
    {
        // Prevent overlapping runs if a batch ever takes longer than the 1-minute cron interval.
        $lock = Cache::lock('crawler:process:lock', 55);

        if (! $lock->get()) {
            $this->warn('Another crawler:process run is still in progress — skipping this tick.');

            return self::SUCCESS;
        }

        try {
            $batchSize = (int) $this->option('batch-size');

            $query = Audit::whereIn('status', [Audit::STATUS_QUEUED, Audit::STATUS_CRAWLING]);

            if ($auditId = $this->option('audit')) {
                $query->where('id', $auditId);
            }

            $audits = $query->get();

            if ($audits->isEmpty()) {
                $this->info('No active audits to process.');

                return self::SUCCESS;
            }

            foreach ($audits as $audit) {
                $this->info("Processing audit #{$audit->id} (batch size {$batchSize})...");

                try {
                    $crawler->processBatch($audit, $batchSize);
                } catch (\Throwable $e) {
                    report($e);
                    $audit->update([
                        'status' => Audit::STATUS_FAILED,
                        'error_message' => mb_substr($e->getMessage(), 0, 1000),
                        'finished_at' => now(),
                    ]);
                    $this->error("Audit #{$audit->id} failed: {$e->getMessage()}");
                }
            }

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
