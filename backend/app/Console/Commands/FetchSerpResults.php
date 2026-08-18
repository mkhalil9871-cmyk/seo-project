<?php

namespace App\Console\Commands;

use App\Models\Keyword;
use App\Models\SerpResult;
use App\Services\SerpService;
use Illuminate\Console\Command;

class FetchSerpResults extends Command
{
    protected $signature = 'serp:fetch {--batch=10}';
    protected $description = 'Fetch SERP results for pending keywords';

    public function handle(SerpService $serp): int
    {
        $batchSize = (int) $this->option('batch');

        $keywords = Keyword::where('serp_status', 'pending')
            ->limit($batchSize)
            ->get();

        foreach ($keywords as $keyword) {
            $keyword->update(['serp_status' => 'processing']);

            $results = $serp->search($keyword->keyword);

            if (empty($results)) {
                $keyword->update(['serp_status' => 'failed', 'last_checked_at' => now()]);
                continue;
            }

            foreach ($results as $result) {
                SerpResult::create([
                    'keyword_id' => $keyword->id,
                    'position'   => $result['position'] ?? null,
                    'url'        => $result['link'] ?? null,
                    'title'      => $result['title'] ?? null,
                    'raw'        => $result,
                    'checked_at' => now(),
                ]);
            }

            $keyword->update(['serp_status' => 'done', 'last_checked_at' => now()]);
        }

        $this->info("Processed {$keywords->count()} keyword(s).");
        return self::SUCCESS;
    }
}