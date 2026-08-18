<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SerpService
{
    public function search(string $keyword, string $country = 'us'): array
    {
        return match (config('services.serp.provider')) {
            'dataforseo' => $this->searchDataForSeo($keyword, $country),
            default => $this->searchSerper($keyword, $country),
        };
    }

    private function searchSerper(string $keyword, string $country): array
    {
        $response = Http::withHeaders([
                'X-API-KEY' => config('services.serper.key'),
                'Content-Type' => 'application/json',
            ])
            ->timeout(15)
            ->post('https://google.serper.dev/search', [
                'q' => $keyword,
                'gl' => $country,
            ]);

        return $response->successful() ? $response->json('organic', []) : [];
    }

    private function searchDataForSeo(string $keyword, string $country): array
    {
        // TODO: implement once DATAFORSEO_LOGIN / DATAFORSEO_PASSWORD are set in .env
        return [];
    }
}