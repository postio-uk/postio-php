<?php

declare(strict_types=1);

namespace Postio;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use Postio\Exception\PostioConnectionException;
use Postio\Exception\PostioException;
use Postio\Exception\PostioForbiddenException;
use Postio\Exception\PostioInvalidKeyException;
use Postio\Exception\PostioNotFoundException;
use Postio\Exception\PostioOutOfCreditException;
use Postio\Exception\PostioRateLimitException;
use Postio\Exception\PostioServerException;
use Postio\Exception\PostioTimeoutException;
use Postio\Exception\PostioValidationException;
use Postio\Resource\AddressResource;
use Postio\Resource\EmailResource;
use Postio\Resource\PhoneResource;
use Postio\Model\ConnectSuccess;

/**
 * Postio API client. Sync only — PHP's runtime story for async is
 * fragmented (ReactPHP, Amphp, Fibers) and SDK customers overwhelmingly
 * want blocking calls.
 *
 * Quick start:
 *
 *     $client = new \Postio\PostioClient(apiKey: 'pk_live_...');
 *     $result = $client->address->search('downing street');
 *     foreach ($result->results as $hit) {
 *         echo $hit->udprn . ': ' . $hit->suggestion . PHP_EOL;
 *     }
 */
final class PostioClient
{
    public const VERSION         = '0.1.1';
    public const DEFAULT_BASE_URL = 'https://api.postio.co.uk/v1';

    public readonly AddressResource $address;
    public readonly EmailResource $email;
    public readonly PhoneResource $phone;

    private readonly string $apiKey;
    private readonly string $baseUrl;
    private readonly ClientInterface $http;

    /**
     * @param string|null $apiKey API key, or null to read POSTIO_API_KEY env var.
     * @param string $baseUrl Override for self-hosted / stage testing.
     * @param float $timeout Per-request timeout in seconds.
     * @param int $retries Retries on 408/409/429/5xx + network errors. 0 disables.
     * @param array<string,string> $headers Extra headers merged into every request.
     * @param ClientInterface|null $http Inject a custom Guzzle client (proxies, mock).
     */
    public function __construct(
        ?string $apiKey = null,
        string $baseUrl = self::DEFAULT_BASE_URL,
        public readonly float $timeout = 10.0,
        public readonly int $retries = 2,
        public readonly array $headers = [],
        ?ClientInterface $http = null,
    ) {
        $key = $apiKey ?? getenv('POSTIO_API_KEY') ?: null;
        if (!is_string($key) || $key === '') {
            throw new \InvalidArgumentException('Postio: api key is required (pass $apiKey or set POSTIO_API_KEY).');
        }
        $this->apiKey = $key;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->http = $http ?? new GuzzleClient([
            'timeout' => $timeout,
            'connect_timeout' => $timeout,
        ]);

        $this->address = new AddressResource($this);
        $this->email = new EmailResource($this);
        $this->phone = new PhoneResource($this);
    }

    public function connect(): ConnectSuccess
    {
        return ConnectSuccess::fromArray($this->request('/connect'));
    }

    /**
     * @internal Used by resource classes.
     * @return array<string,mixed>
     */
    public function request(string $path, array $query = []): array
    {
        $url = $this->baseUrl . $path;
        $query = array_filter($query, fn ($v) => $v !== null);

        $headers = array_merge($this->headers, [
            'x-api-key' => $this->apiKey,
            'Accept' => 'application/json',
            'User-Agent' => 'postio-php/' . self::VERSION,
            'x-postio-client' => 'postio-php/' . self::VERSION,
        ]);

        $maxAttempts = $this->retries + 1;
        $lastException = null;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                $response = $this->http->request('GET', $url, [
                    RequestOptions::HEADERS => $headers,
                    RequestOptions::QUERY => $query,
                    RequestOptions::HTTP_ERRORS => false,
                ]);
            } catch (ConnectException $e) {
                $lastException = new PostioConnectionException(
                    message: 'Network error: ' . $e->getMessage(),
                    errorCode: 'network_error',
                    previous: $e,
                );
                if (!$this->shouldRetryNetwork() || $attempt === $maxAttempts - 1) {
                    throw $lastException;
                }
                usleep((int) ($this->backoffSeconds($attempt) * 1_000_000));
                continue;
            } catch (RequestException $e) {
                if ($e->getMessage() && stripos($e->getMessage(), 'timed out') !== false) {
                    $lastException = new PostioTimeoutException(
                        message: 'Request timed out.',
                        errorCode: 'request_timeout',
                        previous: $e,
                    );
                } else {
                    $lastException = new PostioConnectionException(
                        message: $e->getMessage(),
                        errorCode: 'network_error',
                        previous: $e,
                    );
                }
                if ($attempt === $maxAttempts - 1) {
                    throw $lastException;
                }
                usleep((int) ($this->backoffSeconds($attempt) * 1_000_000));
                continue;
            } catch (TransferException|GuzzleException $e) {
                throw new PostioException(
                    message: 'Postio: transport failure: ' . $e->getMessage(),
                    previous: $e,
                );
            }

            $status = $response->getStatusCode();
            $contentType = $response->getHeaderLine('Content-Type');
            $bodyText = (string) $response->getBody();

            if (!str_contains(strtolower($contentType), 'application/json')) {
                throw new PostioException(
                    message: 'Unexpected response content-type: ' . $contentType,
                    status: $status,
                    errorCode: 'unexpected_content_type',
                    details: substr($bodyText, 0, 500),
                );
            }

            $body = json_decode($bodyText, true);
            if (!is_array($body)) {
                throw new PostioException(
                    message: 'Failed to parse response body as JSON.',
                    status: $status,
                    errorCode: 'parse_error',
                );
            }

            if ($status >= 200 && $status < 300) {
                return $body;
            }

            // Retryable 5xx?
            if ($this->isRetryableStatus($status) && $attempt < $maxAttempts - 1) {
                $lastException = $this->buildException($status, $body, $response);
                usleep((int) ($this->backoffSeconds($attempt) * 1_000_000));
                continue;
            }

            throw $this->buildException($status, $body, $response);
        }

        // Unreachable: loop above either returns or throws.
        throw $lastException ?? new PostioException('Postio: retry loop exhausted unexpectedly.');
    }

    private function buildException(int $status, array $envelope, \Psr\Http\Message\ResponseInterface $response): PostioException
    {
        $error = isset($envelope['error']) ? (string) $envelope['error'] : 'HTTP ' . $status;
        $details = isset($envelope['details']) && $envelope['details'] !== null ? (string) $envelope['details'] : null;
        $requestId = $envelope['meta']['requestId'] ?? null;

        $args = [
            'message' => $error,
            'status' => $status,
            'errorCode' => isset($envelope['error']) ? (string) $envelope['error'] : null,
            'details' => $details,
            'requestId' => is_string($requestId) ? $requestId : null,
            'envelope' => $envelope,
        ];

        return match (true) {
            $status === 401 => new PostioInvalidKeyException(...$args),
            $status === 402 => new PostioOutOfCreditException(...$args),
            $status === 403 => new PostioForbiddenException(...$args),
            $status === 404 => new PostioNotFoundException(...$args),
            $status === 400, $status === 422 => new PostioValidationException(...$args),
            $status === 429 => new PostioRateLimitException(
                ...$args,
                retryAfter: $this->parseRetryAfter($response->getHeaderLine('Retry-After')),
            ),
            $status >= 500 => new PostioServerException(...$args),
            default => new PostioException(...$args),
        };
    }

    private function parseRetryAfter(string $header): ?float
    {
        if ($header === '') {
            return null;
        }
        return is_numeric($header) ? (float) $header : null;
    }

    private function isRetryableStatus(int $status): bool
    {
        return in_array($status, [408, 409, 429, 500, 502, 503, 504], true);
    }

    private function shouldRetryNetwork(): bool
    {
        return $this->retries > 0;
    }

    private function backoffSeconds(int $attempt): float
    {
        $base = 0.5;
        $cap = 8.0;
        $exp = min($cap, $base * (2 ** $attempt));
        return mt_rand(0, (int) ($exp * 1000)) / 1000;
    }
}
