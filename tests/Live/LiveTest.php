<?php

declare(strict_types=1);

namespace Postio\Tests\Live;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Postio\PostioClient;

/**
 * Live tests against api.postio.co.uk (or stage). Skipped automatically
 * when no key is in env. To run them locally:
 *
 *     set -a && source ../.env && set +a
 *     vendor/bin/phpunit --testsuite live
 */
final class LiveTest extends TestCase
{
    private function newClient(): PostioClient
    {
        if ($key = getenv('POSTIO_API_KEY_STAGE')) {
            return new PostioClient(apiKey: $key, baseUrl: 'https://stage-api.postio.co.uk/v1');
        }
        if ($key = getenv('POSTIO_API_KEY_PROD')) {
            return new PostioClient(apiKey: $key);
        }
        if ($key = getenv('POSTIO_API_KEY')) {
            return new PostioClient(apiKey: $key);
        }
        $this->markTestSkipped('no POSTIO_API_KEY* env var set');
    }

    #[Test]
    public function connect(): void
    {
        $r = $this->newClient()->connect();
        $this->assertTrue($r->success);
        $this->assertNotEmpty($r->meta->requestId);
    }

    #[Test]
    public function addressSearch(): void
    {
        $r = $this->newClient()->address->search('downing street', maxResults: 3);
        $this->assertTrue($r->success);
        $this->assertNotEmpty($r->results);
        $this->assertGreaterThan(0, $r->results[0]->udprn);
    }

    #[Test]
    public function emailValidate(): void
    {
        $r = $this->newClient()->email->validate('admin@postio.co.uk');
        $this->assertTrue($r->success);
        $this->assertCount(1, $r->results);
        $this->assertTrue($r->results[0]->isValidSyntax);
    }

    #[Test]
    public function phoneValidate(): void
    {
        $r = $this->newClient()->phone->validate('+442079460000');
        $this->assertTrue($r->success);
        $this->assertCount(1, $r->results);
        $this->assertTrue($r->results[0]->isValid);
    }
}
