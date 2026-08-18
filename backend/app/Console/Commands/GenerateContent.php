<?php

namespace App\Console\Commands;

use App\Models\ContentPiece;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class GenerateContent extends Command
{
    protected $signature = 'content:generate {--batch=5}';
    protected $description = 'Generate content pieces for pending requests';

    public function handle(OpenAIService $ai): int
    {
        $batchSize = (int) $this->option('batch');

        $pieces = ContentPiece::where('status', 'pending')
            ->limit($batchSize)
            ->get();

        foreach ($pieces as $piece) {
            $piece->update(['status' => 'processing']);

            $keyword = $piece->keyword?->keyword ?? $piece->project->name;

            $result = $ai->generateContent($keyword);

            if (empty($result)) {
                $piece->update(['status' => 'failed', 'generated_at' => now()]);
                continue;
            }

            $piece->update([
                'title' => $result['title'] ?? null,
                'body' => $result['body'] ?? null,
                'status' => 'done',
                'generated_at' => now(),
            ]);
        }

        $this->info("Processed {$pieces->count()} content piece(s).");
        return self::SUCCESS;
    }
}