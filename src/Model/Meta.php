<?php

declare(strict_types=1);

namespace Postio\Model;

final class Meta
{
    public function __construct(
        public readonly int $countResults,
        public readonly string $requestId,
        public readonly Performance $performance,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            countResults: (int) $data['countResults'],
            requestId: (string) $data['requestId'],
            performance: Performance::fromArray($data['performance']),
        );
    }
}
