<?php

declare(strict_types=1);

namespace Postio\Model;

/**
 * SPEC DRIFT (2026-05-02): the OpenAPI spec marks every nullable field
 * as `required` with type [string, null], but on invalid input the live
 * API drops them entirely. We default every nullable field to null so
 * customer code doesn't see a parse error on real responses. Also: the
 * spec says `isReachable` is string|null, but the live API returns a
 * bool — we accept either. Reapply this block after any future regen.
 */
final class PhoneResult
{
    public function __construct(
        public readonly string $number,
        public readonly bool $isValid,
        public readonly bool $isPossible,
        public readonly ?string $type = null,
        public readonly ?string $countryCode = null,
        public readonly ?string $countryName = null,
        public readonly ?string $nationalFormat = null,
        public readonly ?string $internationalFormat = null,
        public readonly ?string $e164Format = null,
        public readonly ?string $originalCarrier = null,
        public readonly ?string $currentCarrier = null,
        public readonly ?bool $isPorted = null,
        public readonly bool|string|null $isReachable = null,
        public readonly ?string $mcc = null,
        public readonly ?string $mnc = null,
        public readonly ?string $level = null,
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
            isReachable: $data['isReachable'] ?? null,
            mcc: $data['mcc'] ?? null,
            mnc: $data['mnc'] ?? null,
            level: $data['level'] ?? null,
            lookupError: $data['lookupError'] ?? null,
        );
    }
}
