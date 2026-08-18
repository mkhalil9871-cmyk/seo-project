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
                        'content' => "You are an SEO strategist. Based on this project's audit issues and keyword data, suggest a prioritized content and SEO strategy:\n\n" . json_encode($projectData),
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

    public function generateContent(string $keyword): array
    {
        $response = Http::withToken(config('services.openai.key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Write a complete, well-structured SEO blog article targeting the keyword \"{$keyword}\". Respond in this exact format:\nTITLE: <article title>\nBODY:\n<full article body>",
                    ],
                ],
            ]);

        if (! $response->successful()) {
            return [];
        }

        $text = $response->json('choices.0.message.content', '');

        return $this->parseTitleAndBody($text);
    }

    private function parseTitleAndBody(string $text): array
    {
        if (preg_match('/TITLE:\s*(.+)\nBODY:\s*(.*)/is', $text, $matches)) {
            return [
                'title' => trim($matches[1]),
                'body'  => trim($matches[2]),
            ];
        }

        return ['title' => null, 'body' => $text];
    }
}