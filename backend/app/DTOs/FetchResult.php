<?php

namespace App\DTOs;

final class FetchResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?int $statusCode,
        public readonly ?string $body,
        public readonly ?string $finalUrl,   // after redirects
        public readonly ?string $redirectTo, // single-hop redirect target, if any
        public readonly int $responseTimeMs,
        public readonly ?string $contentType,
        public readonly ?string $errorMessage = null,
        public readonly ?string $xRobotsTag = null,
    ) {
    }

    public static function failure(string $errorMessage, int $responseTimeMs = 0): self
    {
        return new self(
            success: false,
            statusCode: null,
            body: null,
            finalUrl: null,
            redirectTo: null,
            responseTimeMs: $responseTimeMs,
            contentType: null,
            errorMessage: $errorMessage,
            xRobotsTag: null,
        );
    }
}
