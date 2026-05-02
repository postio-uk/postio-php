<?php

declare(strict_types=1);

namespace Postio\Model;

final class EmailResult
{
    public const DELIVERABILITY_DELIVERABLE   = 'deliverable';
    public const DELIVERABILITY_UNDELIVERABLE = 'undeliverable';
    public const DELIVERABILITY_RISKY         = 'risky';
    public const DELIVERABILITY_UNKNOWN       = 'unknown';
    public const DELIVERABILITY_INVALID       = 'invalid';

    public function __construct(
        public readonly string $email,
        public readonly bool $isValidSyntax,
        public readonly ?string $didYouMean,
        public readonly bool $isDisposable,
        public readonly bool $isFreeProvider,
        public readonly bool $isRoleAccount,
        public readonly bool $mxFound,
        public readonly ?string $smtpCheck,
        public readonly ?bool $isCatchAll,
        public readonly string $deliverability,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: (string) $data['email'],
            isValidSyntax: (bool) $data['isValidSyntax'],
            didYouMean: $data['didYouMean'] ?? null,
            isDisposable: (bool) $data['isDisposable'],
            isFreeProvider: (bool) $data['isFreeProvider'],
            isRoleAccount: (bool) $data['isRoleAccount'],
            mxFound: (bool) $data['mxFound'],
            smtpCheck: $data['smtpCheck'] ?? null,
            isCatchAll: array_key_exists('isCatchAll', $data) ? ($data['isCatchAll'] === null ? null : (bool) $data['isCatchAll']) : null,
            deliverability: (string) $data['deliverability'],
        );
    }
}
