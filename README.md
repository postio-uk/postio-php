# Postio PHP SDK

[![Packagist](https://img.shields.io/packagist/v/postio/postio.svg)](https://packagist.org/packages/postio/postio)
[![PHP Version](https://img.shields.io/packagist/php-v/postio/postio.svg)](https://packagist.org/packages/postio/postio)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

PHP SDK for [Postio](https://postio.co.uk) — the UK validation API for
addresses, emails and phone numbers. PSR-18 over Guzzle, typed `readonly`
value objects. Backed by Royal Mail PAF and Ordnance Survey.

> **First time?** [Sign up free](https://postio.co.uk) — first 100 lookups on us, no card needed.

## Install

```bash
composer require postio/postio
```

Requires PHP 8.1+.

## 30-second example

```php
<?php

require 'vendor/autoload.php';

use Postio\PostioClient;

$client = new PostioClient(apiKey: 'pk_...');  // or set POSTIO_API_KEY

$result = $client->address->search('downing street');
foreach ($result->results as $hit) {
    echo $hit->udprn . ': ' . $hit->suggestion . PHP_EOL;
}

echo 'request id: ' . $result->meta->requestId . PHP_EOL;
```

## API

| Method | Returns |
|---|---|
| `$client->address->search($q, $maxResults)` | `AddressSearchEnvelope` |
| `$client->address->postcode($postcode, $maxResults)` | `AddressPostcodeEnvelope` |
| `$client->address->udprn($udprn)` | `AddressUdprnEnvelope` |
| `$client->email->validate($address)` | `EmailEnvelope` |
| `$client->phone->validate($number)` | `PhoneEnvelope` |
| `$client->connect()` | `ConnectSuccess` |

## Errors

Every non-2xx response throws a typed exception. `PostioException` is the
base, with subclasses per status code:

```php
use Postio\Exception\PostioInvalidKeyException;
use Postio\Exception\PostioOutOfCreditException;
use Postio\Exception\PostioRateLimitException;

try {
    $client->address->postcode('not-a-postcode');
} catch (PostioInvalidKeyException $e) {
    // 401
} catch (PostioOutOfCreditException $e) {
    // 402
} catch (PostioRateLimitException $e) {
    // 429 — $e->retryAfter has the suggested wait in seconds
}
```

Every exception carries `status`, `errorCode`, `details`, `requestId`, and
`envelope`. Quote `requestId` when reporting issues.

## Configuration

```php
$client = new PostioClient(
    apiKey: 'pk_...',
    baseUrl: 'https://api.postio.co.uk/v1',  // default
    timeout: 10.0,                            // seconds
    retries: 2,                                // 0 to disable
    headers: ['x-tracking-id' => '...'],       // extra headers
);
```

Default retry policy: 2 retries on 408/409/429/5xx + network/timeout,
exponential backoff with full jitter (500ms → 8s cap).

## Frameworks

The SDK is framework-agnostic. Inject `PostioClient` once via your
container:

**Laravel** — bind in a service provider:

```php
$this->app->singleton(PostioClient::class, fn () => new PostioClient(
    apiKey: config('services.postio.key'),
));
```

**Symfony** — register as a service in `services.yaml`:

```yaml
Postio\PostioClient:
    arguments:
        $apiKey: '%env(POSTIO_API_KEY)%'
```

## Links

- [Docs](https://postio.co.uk/docs)
- [API reference (OpenAPI)](https://postio.co.uk/openapi.json)
- [Changelog](./CHANGELOG.md)
- [Issues](https://github.com/postio-uk/postio-php/issues)

## License

MIT — see [LICENSE](./LICENSE).

> *Postio is a trading name of Onno Group Limited, registered in
> England & Wales (company no. 08622799). Registered office:
> Suite 22 Trym Lodge, 1 Henbury Road, Westbury-On-Trym, Bristol BS9 3HQ.*
