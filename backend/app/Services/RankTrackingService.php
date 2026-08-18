<?php

namespace App\Services;

class RankTrackingService
{
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.dataforseo.key');
    }

    /**
     * Check the rank position of a keyword.
     * Returns null gracefully if no API key is configured yet.
     */
    public function checkPosition(string $keyword, string $domain): ?array
    {
        if (empty($this->apiKey)) {
            // No API key yet — graceful no-op, same pattern as Strategy/Content Engine
            return null;
        }

        // TODO: once we have a DataForSEO key, real API call goes here
        return null;
    }
}