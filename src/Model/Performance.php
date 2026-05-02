<?php

declare(strict_types=1);

namespace Postio\Model;

final class Performance
{
    public function __construct(
        public readonly int $workerMs,
        public readonly int $lookupMs,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            workerMs: (int) $data['workerMs'],
            lookupMs: (int) $data['lookupMs'],
        );
    }
}
