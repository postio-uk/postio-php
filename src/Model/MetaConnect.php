<?php

declare(strict_types=1);

namespace Postio\Model;

final class MetaConnect
{
    public function __construct(
        public readonly string $requestId,
        public readonly Performance $performance,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            requestId: (string) $data['requestId'],
            performance: Performance::fromArray($data['performance']),
        );
    }
}
