<?php

declare(strict_types=1);

namespace Postio\Model;

final class PhoneResult
{
    public function __construct(
        public readonly string $number,
        public readonly bool $isValid,
        public readonly bool $isPossible,
        public readonly ?string $type,
        public readonly ?string $countryCode,
        public readonly ?string $countryName,
        public readonly ?string $nationalFormat,
        public readonly ?string $internationalFormat,
        public readonly ?string $e164Format,
        public readonly ?string $originalCarrier,
        public readonly ?string $currentCarrier,
        public readonly ?bool $isPorted,
        public readonly ?bool $isReachable,
        public readonly ?string $mcc,
        public readonly ?string $mnc,
        public readonly ?string $level,
        public readonly ?string $lookupError = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            number: (string) $data['number'],
            isValid: (bool) $data['isValid'],
            isPossible: (bool) $data['isPossible'],
            type: $data['type'] ?? null,
            countryCode: $data['countryCode'] ?? null,
            countryName: $data['countryName'] ?? null,
            nationalFormat: $data['nationalFormat'] ?? null,
            internationalFormat: $data['internationalFormat'] ?? null,
            e164Format: $data['e164Format'] ?? null,
            originalCarrier: $data['originalCarrier'] ?? null,
            currentCarrier: $data['currentCarrier'] ?? null,
            isPorted: array_key_exists('isPorted', $data) ? ($data['isPorted'] === null ? null : (bool) $data['isPorted']) : null,
            isReachable: array_key_exists('isReachable', $data) ? ($data['isReachable'] === null ? null : (bool) $data['isReachable']) : null,
            mcc: $data['mcc'] ?? null,
            mnc: $data['mnc'] ?? null,
            level: $data['level'] ?? null,
            lookupError: $data['lookupError'] ?? null,
        );
    }
}
