<?php

declare(strict_types=1);

namespace Postio\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Postio\Exception\PostioException;
use Postio\Exception\PostioInvalidKeyException;
use Postio\Exception\PostioOutOfCreditException;
use Postio\Exception\PostioRateLimitException;
use Postio\Exception\PostioServerException;
use Postio\PostioClient;

/**
 * Offline tests — no network, no key required. Uses Guzzle's MockHandler.
 */
final class PostioClientTest extends TestCase
{
    private function clientWithResponses(array $responses, int $retries = 0): PostioClient
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $http = new Client(['handler' => $stack]);
        return new PostioClient(
            apiKey: 'pk_test',
            retries: $retries,
            http: $http,
        );
    }

    private function envelope(int $status, array $body, array $headers = []): Response
    {
        return new Response($status, array_merge(['Content-Type' => 'application/json'], $headers), json_encode($body));
    }

    #[Test]
    public function searchReturnsTypedEnvelope(): void
    {
        $client = $this->clientWithResponses([
            $this->envelope(200, [
                'success' => true,
                'results' => [['udprn' => 12345, 'suggestion' => '10 Downing Street']],
                'meta' => [
                    'countResults' => 1,
                    'requestId' => 'abc-123',
                    'performance' => ['workerMs' => 10, 'lookupMs' => 5],
                ],
            ]),
        ]);

        $r = $client->address->search('downing', maxResults: 5);
        $this->assertTrue($r->success);
        $this->assertCount(1, $r->results);
        $this->assertSame(12345, $r->results[0]->udprn);
        $this->assertSame('10 Downing Street', $r->results[0]->suggestion);
        $this->assertSame('abc-123', $r->meta->requestId);
    }

    #[Test]
    public function status401MapsToInvalidKey(): void
    {
        $client = $this->clientWithResponses([
            $this->envelope(401, [
                'success' => false,
                'error' => 'invalid_api_key',
                'details' => 'Key not recognised',
                'results' => [],
                'meta' => ['countResults' => 0, 'requestId' => 'r-401', 'performance' => ['workerMs' => 1, 'lookupMs' => 0]],
            ]),
        ]);

        try {
            $client->connect();
            $this->fail('expected exception');
        } catch (PostioInvalidKeyException $e) {
            $this->assertSame(401, $e->status);
            $this->assertSame('invalid_api_key', $e->errorCode);
            $this->assertSame('Key not recognised', $e->details);
            $this->assertSame('r-401', $e->requestId);
        }
    }

    #[Test]
    public function status402MapsToOutOfCredit(): void
    {
        $client = $this->clientWithResponses([
            $this->envelope(402, [
                'success' => false,
                'error' => 'out_of_credit',
                'results' => [],
                'meta' => ['countResults' => 0, 'requestId' => 'r-402', 'performance' => ['workerMs' => 1, 'lookupMs' => 0]],
            ]),
        ]);

        $this->expectException(PostioOutOfCreditException::class);
        $client->connect();
    }

    #[Test]
    public function status429SurfacesRetryAfter(): void
    {
        $client = $this->clientWithResponses([
            $this->envelope(429, [
                'success' => false,
                'error' => 'rate_limited',
                'results' => [],
                'meta' => ['countResults' => 0, 'requestId' => 'r-429', 'performance' => ['workerMs' => 1, 'lookupMs' => 0]],
            ], ['Retry-After' => '12']),
        ]);

        try {
            $client->connect();
            $this->fail('expected exception');
        } catch (PostioRateLimitException $e) {
            $this->assertSame(12.0, $e->retryAfter);
            $this->assertSame(429, $e->status);
        }
    }

    #[Test]
    public function status500RetriedThenSucceeds(): void
    {
        $client = $this->clientWithResponses([
            $this->envelope(503, [
                'success' => false,
                'error' => 'unavailable',
                'results' => [],
                'meta' => ['countResults' => 0, 'requestId' => 'r1', 'performance' => ['workerMs' => 1, 'lookupMs' => 0]],
            ]),
            $this->envelope(200, [
                'success' => true,
                'meta' => ['requestId' => 'r-ok', 'performance' => ['workerMs' => 5, 'lookupMs' => 2]],
            ]),
        ], retries: 2);

        $r = $client->connect();
        $this->assertTrue($r->success);
        $this->assertSame('r-ok', $r->meta->requestId);
    }

    #[Test]
    public function status500ExhaustedRaisesServerError(): void
    {
        $client = $this->clientWithResponses([
            $this->envelope(500, [
                'success' => false,
                'error' => 'internal',
                'results' => [],
                'meta' => ['countResults' => 0, 'requestId' => 'r1', 'performance' => ['workerMs' => 1, 'lookupMs' => 0]],
            ]),
            $this->envelope(500, [
                'success' => false,
                'error' => 'internal',
                'results' => [],
                'meta' => ['countResults' => 0, 'requestId' => 'r2', 'performance' => ['workerMs' => 1, 'lookupMs' => 0]],
            ]),
        ], retries: 1);

        $this->expectException(PostioServerException::class);
        $client->connect();
    }

    #[Test]
    public function constructorRequiresApiKey(): void
    {
        putenv('POSTIO_API_KEY');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/api key is required/i');
        new PostioClient();
    }

    #[Test]
    public function userAgentIncludesSdkVersion(): void
    {
        $captured = null;
        $stack = HandlerStack::create(new MockHandler([
            $this->envelope(200, [
                'success' => true,
                'meta' => ['requestId' => 'ok', 'performance' => ['workerMs' => 1, 'lookupMs' => 0]],
            ]),
        ]));
        $stack->push(\GuzzleHttp\Middleware::tap(function ($request) use (&$captured) {
            $captured = $request->getHeaderLine('User-Agent');
        }));
        $http = new Client(['handler' => $stack]);
        $client = new PostioClient(apiKey: 'pk_test', http: $http);
        $client->connect();
        $this->assertStringStartsWith('postio-php/', (string) $captured);
    }
}
