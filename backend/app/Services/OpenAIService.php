<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    public function generateStrategy(array $projectData): array
    {
        $response = Http::withToken(config('services.openai.key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($projectData),
                    ],
                ],
            ]);

        if (! $response->successful()) {
            return [];
        }

        return [
            'text' => $response->json('choices.0.message.content'),
            'raw'  => $response->json(),
        ];
    }

    private function buildPrompt(array $projectData): string
    {
        $summary = json_encode($projectData);

        return "You are an SEO strategist. Based on this project's audit issues and keyword data, suggest a prioritized content and SEO strategy:\n\n{$summary}";
    }
}