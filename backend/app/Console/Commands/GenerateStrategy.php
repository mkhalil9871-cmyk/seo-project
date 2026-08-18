<?php

namespace App\Console\Commands;

use App\Models\Strategy;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class GenerateStrategy extends Command
{
    protected $signature = 'strategy:generate {--batch=5}';
    protected $description = 'Generate SEO strategies for pending strategy requests';

    public function handle(OpenAIService $ai): int
    {
        $batchSize = (int) $this->option('batch');

        $strategies = Strategy::where('status', 'pending')
            ->limit($batchSize)
            ->get();

        foreach ($strategies as $strategy) {
            $strategy->update(['status' => 'processing']);

            $project = $strategy->project()->with('audits.crawledPages')->first();
            $projectData = $project->toArray();

            $result = $ai->generateStrategy($projectData);

            if (empty($result)) {
                $strategy->update(['status' => 'failed', 'generated_at' => now()]);
                continue;
            }

            $strategy->update([
                'content' => $result,
                'status' => 'done',
                'generated_at' => now(),
            ]);
        }

        $this->info("Processed {$strategies->count()} strategy request(s).");
        return self::SUCCESS;
    }
}