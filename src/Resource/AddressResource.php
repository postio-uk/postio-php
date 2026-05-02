<?php

declare(strict_types=1);

namespace Postio\Resource;

use Postio\PostioClient;
use Postio\Model\AddressPostcodeEnvelope;
use Postio\Model\AddressSearchEnvelope;
use Postio\Model\AddressUdprnEnvelope;

final class AddressResource
{
    public function __construct(private readonly PostioClient $client) {}

    public function search(string $q, ?int $maxResults = null): AddressSearchEnvelope
    {
        $body = $this->client->request('/address/search', ['q' => $q, 'max_results' => $maxResults]);
        return AddressSearchEnvelope::fromArray($body);
    }

    public function postcode(string $postcode, ?int $maxResults = null): AddressPostcodeEnvelope
    {
        $body = $this->client->request('/address/postcode/' . rawurlencode($postcode), ['max_results' => $maxResults]);
        return AddressPostcodeEnvelope::fromArray($body);
    }

    public function udprn(int|string $udprn): AddressUdprnEnvelope
    {
        $body = $this->client->request('/address/udprn/' . rawurlencode((string) $udprn));
        return AddressUdprnEnvelope::fromArray($body);
    }
}
