# Changelog

All notable changes to `postio/postio` are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), versioning
follows [SemVer](https://semver.org/).

## [Unreleased]

## [0.1.0] — 2026-05-02

Initial release. First Postio PHP SDK on Packagist.

### Added

- `Postio\PostioClient` with sync API (PHP's async story is too
  fragmented to ship two surfaces).
- Address: `$client->address->search/postcode/udprn`.
- Email: `$client->email->validate`.
- Phone: `$client->phone->validate`.
- Health probe: `$client->connect()`.
- Hand-written `readonly` value objects for every response shape.
- Typed exception hierarchy: `PostioException` base +
  `PostioInvalidKeyException`, `PostioOutOfCreditException`,
  `PostioForbiddenException`, `PostioNotFoundException`,
  `PostioValidationException`, `PostioRateLimitException`,
  `PostioServerException`, `PostioTimeoutException`,
  `PostioConnectionException`. Each carries `status`, `errorCode`,
  `details`, `requestId`, `envelope`.
- Default retry policy (2 retries, exp backoff + full jitter on
  408/409/429/5xx + network/timeout). Mirrors `@postio/node`.
- Guzzle 7 as the HTTP transport — inject your own `ClientInterface`
  via the `$http` constructor arg for proxies, mocks, custom middleware.
- `POSTIO_API_KEY` env var fallback when no `apiKey` argument is passed.

### Notes

- `PhoneResult.isReachable` is typed `bool|string|null` because the
  live API returns booleans there even though the spec says
  string-only. Aligned once postio-api ships a spec/runtime fix.
- Property `errorCode` (not `code`) — PHP's `Exception::$code` is a
  built-in non-readonly int and can't be shadowed by a readonly
  string.

[Unreleased]: https://github.com/postio-uk/postio-php/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/postio-uk/postio-php/releases/tag/v0.1.0
