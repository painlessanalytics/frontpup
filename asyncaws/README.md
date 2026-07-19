# AsyncAws CloudFront Bundle (trimmed)

This folder contains a Composer-built, then manually trimmed, copy of the
[AsyncAws](https://async-aws.com/) client libraries needed to call the
CloudFront `CreateInvalidation` API. It is loaded only when the "AsyncAws SDK"
option is selected in FrontPup's Clear Cache settings.

## Packages vendored

- `async-aws/cloud-front` 1.0.4
- `async-aws/core` 1.27.1
- `symfony/http-client` ^6.4 (+ `http-client-contracts`, `service-contracts`,
  `deprecation-contracts`, `polyfill-php83`)
- `psr/log`, `psr/cache`, `psr/container`

Versions are pinned to the newest releases that still support PHP 8.1 (this
plugin's minimum), since the latest AsyncAws/Symfony releases require PHP 8.2+.

## Rebuilding

```
cd asyncaws
composer install --no-dev -o
```

Then re-apply the trim step below before committing `vendor/`.

## What was removed after `composer install`

Only test suites and documentation were removed: `tests/`, `Tests/`, `Test/`
directories in every package, `symfony/http-client`'s `DependencyInjection/`,
`Messenger/`, and `DataCollector/` integration directories (these wire into
other Symfony components FrontPup doesn't bundle and are never referenced
outside their own namespace), plus `README.md`, `CHANGELOG.md`, `Makefile`,
`phpunit.xml.dist`, and `roave-bc-check.yaml`. `LICENSE` files were kept in
every package for attribution.

**Do not delete individual top-level classes inside `symfony/http-client/`**
(e.g. `HttpOptions.php`, `AsyncDecoratorTrait.php`, `MockHttpClient.php`)
even though they look unused from FrontPup's perspective — several of them
are pulled in via same-namespace `use SomeTrait;` statements (no `use
Symfony\...` import, so it won't show up in a grep for the FQCN) by classes
that genuinely are required, such as `RetryableHttpClient` using
`AsyncDecoratorTrait`. A first attempt at trimming these individually
caused a fatal "Trait not found" error that only surfaced when actually
exercising the CloudFront invalidation call end-to-end, not from `php -l` or
autoloading alone — hence the conservative approach of only removing whole
directories that are unambiguously separate integrations (tests, docs,
other-component adapters).

AsyncAws's own credential provider chain (env vars, `~/.aws` ini files, ECS
task credentials, EC2 IMDSv2, web identity/OIDC, SSO token cache) was left
intact under `async-aws/core/src/Credentials/` — FrontPup's "IAM Role"
credentials mode relies on it directly instead of FrontPup's own
`LightAWS_Base` credential resolution.

After trimming, `composer dump-autoload --no-dev -o` was re-run so the
optimized classmap only references files that still exist. The whole
`createInvalidation()` call path (Configuration → credentials → SigV4
signing → request building → response parsing) was verified twice: once
against a stub HTTP client, and once end-to-end through
`FrontPup_Clear_Cache::clear_cache()` itself, including a real (rejected)
request to `cloudfront.amazonaws.com` to confirm SigV4 signing and AWS's XML
error-response parsing both work.

**Important:** `AsyncAws\Core\Result` objects (like `CreateInvalidationResult`)
resolve their underlying HTTP response lazily. Calling
`$client->createInvalidation(...)` does not synchronously perform/validate
the HTTP request — errors (auth failures, HTTP 4xx/5xx) only surface the
first time a getter on the result is called (or when the object is
destroyed, where they can no longer be caught). `FrontPup_Clear_Cache::clear_cache()`
calls `$response->getInvalidation()` inside its `try` block specifically to
force this resolution eagerly so errors are caught and reported instead of
being silently swallowed.
