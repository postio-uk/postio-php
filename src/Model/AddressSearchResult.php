<?php

declare(strict_types=1);

namespace Postio\Model;

final class AddressSearchResult
{
    public function __construct(
        public readonly int $udprn,
        public readonly string $suggestion,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            udprn: (int) $data['udprn'],
            suggestion: (string) $data['suggestion'],
        );
    }
}
