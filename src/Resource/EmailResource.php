<?php

declare(strict_types=1);

namespace Postio\Resource;

use Postio\PostioClient;
use Postio\Model\EmailEnvelope;

final class EmailResource
{
    public function __construct(private readonly PostioClient $client) {}

    public function validate(string $address): EmailEnvelope
    {
        $body = $this->client->request('/email/' . rawurlencode($address));
        return EmailEnvelope::fromArray($body);
    }
}
