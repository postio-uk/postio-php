<?php

declare(strict_types=1);

namespace Postio\Exception;

/**
 * Base class for every Postio API failure. Carries the API's request ID
 * (the support handle) plus the raw error envelope.
 */
class PostioException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?string $errorCode = null,
        public readonly ?string $details = null,
        public readonly ?string $requestId = null,
        public readonly ?array $envelope = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }
}
