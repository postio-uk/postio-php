<?php

declare(strict_types=1);

namespace Postio\Model;

final class ConnectSuccess
{
    public function __construct(
        public readonly bool $success,
        public readonly MetaConnect $meta,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            success: (bool) $data['success'],
            meta: MetaConnect::fromArray($data['meta']),
        );
    }
}
