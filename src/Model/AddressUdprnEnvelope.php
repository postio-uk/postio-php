<?php

declare(strict_types=1);

namespace Postio\Model;

final class AddressUdprnEnvelope
{
    public function __construct(
        public readonly bool $success,
        /** @var Address[] */
        public readonly array $results,
        public readonly Meta $meta,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            success: (bool) $data['success'],
            results: array_map(fn (array $r) => Address::fromArray($r), $data['results']),
            meta: Meta::fromArray($data['meta']),
        );
    }
}
