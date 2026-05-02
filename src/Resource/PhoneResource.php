<?php

declare(strict_types=1);

namespace Postio\Resource;

use Postio\PostioClient;
use Postio\Model\PhoneEnvelope;

final class PhoneResource
{
    public function __construct(private readonly PostioClient $client) {}

    public function validate(string $number): PhoneEnvelope
    {
        $body = $this->client->request('/phone/' . rawurlencode($number));
        return PhoneEnvelope::fromArray($body);
    }
}
