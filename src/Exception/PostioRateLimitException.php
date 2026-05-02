<?php

declare(strict_types=1);

namespace Postio\Exception;

/** 429 — rate limit hit. {@see retryAfter} for the API-suggested wait. */
class PostioRateLimitException extends PostioException
{
    public function __construct(
        string $message,
        int $status = 429,
        ?string $errorCode = null,
        ?string $details = null,
        ?string $requestId = null,
        ?array $envelope = null,
        public readonly ?float $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $errorCode, $details, $requestId, $envelope, $previous);
    }
}
