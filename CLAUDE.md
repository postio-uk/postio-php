# postio-php — Claude Code working notes

PHP SDK for `postio-api`. Mirrors `@postio/core` with idiomatic PHP
ergonomics. Lives in its own repo because PHP's Composer/Packagist
toolchain doesn't co-exist with the umbrella's pnpm workspace.

Read [`README.md`](./README.md) for the customer-facing surface; this
file is the operational guide.

## Stack

- **PHP 8.1+**. Constructor property promotion, readonly properties,
  named arguments, enums, never type. We deliberately stay on 8.1 as
  the floor for max install share.
- **HTTP**: Guzzle 7 via PSR-18-compatible `ClientInterface`. Customers
  can inject their own client (for proxies, mocks, middleware).
- **Tests**: PHPUnit 10. Offline tests use Guzzle's `MockHandler`;
  live tests are gated to a separate `live` test suite that's only
  run when a stage key is in env.

## Why no codegen

`jane-php/open-api-runtime` exists, but the generated code is
heavyweight (DTO + normalizer + denormalizer per type) and the spec
surface is small. Hand-written `readonly` value objects with a static
`fromArray` factory are simpler, faster, and easier to debug.

## Layout

```
postio-php/
├── composer.json
├── phpunit.xml
├── src/
│   ├── PostioClient.php       sync client, retry loop, error mapping
│   ├── Resource/
│   │   ├── AddressResource.php
│   │   ├── EmailResource.php
│   │   └── PhoneResource.php
│   ├── Model/                  every response shape
│   └── Exception/              one class per HTTP failure mode
├── tests/
│   ├── PostioClientTest.php   offline (MockHandler)
│   └── Live/LiveTest.php       live (skipped if no key in env)
├── README.md / CLAUDE.md / LICENSE / CHANGELOG.md
└── .github/workflows/ci.yml
```

## Common commands

```bash
composer install
vendor/bin/phpunit                          # offline + live
vendor/bin/phpunit --testsuite offline       # offline only
set -a && source ../.env && set +a && vendor/bin/phpunit --testsuite live
```

## Branch + deploy model

- `stage` — working branch.
- `master` — push triggers the live-test job.
- Releases: tag `vX.Y.Z` + push tag. Packagist auto-detects the new
  tag via the GitHub webhook (configured at first
  `packagist.org/packages/submit`) and indexes it within ~1 minute.
- No release workflow file needed. Packagist is the registry; tags
  are the publish trigger. There's no auth-time secret to manage.

## Spec drift

`PhoneResult` carries two manual patches (the spec says
`required` for every nullable field, and types `isReachable` as
string-only when the API returns bools). Mirror of postio-python's
patches. CHANGELOG.md notes them. Reapply if PhoneResult is ever
regenerated.

## Secrets the CI needs

| Secret | Used by |
|---|---|
| `POSTIO_API_KEY_STAGE` | live-test job in `ci.yml`. Pair with `stage-api.postio.co.uk` (handled by the live test). |

No publish secret. Packagist is webhook-driven.

## Tone for this repo

Same as the umbrella: terse, casual, status-emoji summaries.
