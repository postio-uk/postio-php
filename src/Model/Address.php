<?php

declare(strict_types=1);

namespace Postio\Model;

/**
 * Full UK address record from Royal Mail PAF + Ordnance Survey. Most
 * fields are optional and may be null.
 */
final class Address
{
    public function __construct(
        public readonly int $udprn,
        public readonly string $postcode,
        public readonly ?string $postcode_outward = null,
        public readonly ?string $postcode_inward = null,
        public readonly ?string $postcode_type = null,
        public readonly ?string $address_line_1 = null,
        public readonly ?string $address_line_2 = null,
        public readonly ?string $address_line_3 = null,
        public readonly ?string $post_town = null,
        public readonly ?string $organisation_name = null,
        public readonly ?string $department_name = null,
        public readonly ?string $building_name = null,
        public readonly ?string $building_number = null,
        public readonly ?string $sub_building_name = null,
        public readonly ?string $po_box = null,
        public readonly ?string $thoroughfare = null,
        public readonly ?string $dependent_thoroughfare = null,
        public readonly ?string $dependent_locality = null,
        public readonly ?string $double_dependent_locality = null,
        public readonly ?string $delivery_point_suffix = null,
        public readonly ?string $country = null,
        public readonly ?string $district = null,
        public readonly ?string $ward = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?int $eastings = null,
        public readonly ?int $northings = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            udprn: (int) $data['udprn'],
            postcode: (string) $data['postcode'],
            postcode_outward: $data['postcode_outward'] ?? null,
            postcode_inward: $data['postcode_inward'] ?? null,
            postcode_type: $data['postcode_type'] ?? null,
            address_line_1: $data['address_line_1'] ?? null,
            address_line_2: $data['address_line_2'] ?? null,
            address_line_3: $data['address_line_3'] ?? null,
            post_town: $data['post_town'] ?? null,
            organisation_name: $data['organisation_name'] ?? null,
            department_name: $data['department_name'] ?? null,
            building_name: $data['building_name'] ?? null,
            building_number: $data['building_number'] ?? null,
            sub_building_name: $data['sub_building_name'] ?? null,
            po_box: $data['po_box'] ?? null,
            thoroughfare: $data['thoroughfare'] ?? null,
            dependent_thoroughfare: $data['dependent_thoroughfare'] ?? null,
            dependent_locality: $data['dependent_locality'] ?? null,
            double_dependent_locality: $data['double_dependent_locality'] ?? null,
            delivery_point_suffix: $data['delivery_point_suffix'] ?? null,
            country: $data['country'] ?? null,
            district: $data['district'] ?? null,
            ward: $data['ward'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            eastings: isset($data['eastings']) ? (int) $data['eastings'] : null,
            northings: isset($data['northings']) ? (int) $data['northings'] : null,
        );
    }
}
