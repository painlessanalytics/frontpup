# FrontPup – AWS & CloudFront Integration

## Lightweight vs AsyncAws vs Full AWS SDK

FrontPup ships three paths for making CloudFront API calls, selected by the `full_aws_sdk` setting in `frontpup_clear_cache`:

| | Lightweight (default) | AsyncAws | Full AWS SDK |
|---|---|---|---|
| Location | `includes/` | `asyncaws/vendor/` | `aws/` |
| Entry point | `LightAWS_CloudFront` | `AsyncAws\CloudFront\CloudFrontClient` | `Aws\CloudFront\CloudFrontClient` |
| `full_aws_sdk` value | `''` | `'asyncaws'` | `'1'` |
| Autoloader | none needed | `asyncaws/vendor/autoload.php` | `aws/aws-autoloader.php` |
| Operations implemented | createInvalidation, getInvalidation, listInvalidations | createInvalidation only (upstream doesn't ship the other two) | full API |

Always prefer the lightweight path for new CloudFront operations. AsyncAws is a middle ground — real, independently-tested third-party code with a smaller footprint than the full SDK — for environments where the lightweight client's hand-rolled SigV4 signing doesn't work. Only fall back to the full SDK when neither of the other two can support a required API feature.

The `asyncaws/` folder is a Composer-built bundle that was then manually trimmed (test suites, docs, and unused `symfony/http-client` transport integrations removed) — see `asyncaws/README.md` for exactly what was removed and how to rebuild it. Versions are pinned to the newest releases that still support PHP 8.1: `async-aws/core` 1.27.1, `async-aws/cloud-front` 1.0.4, `symfony/http-client` ^6.4. Unlike the lightweight path, the AsyncAws path uses AsyncAws's own credential provider chain (`AsyncAws\Core\Credentials\ChainProvider`) for `policy` mode instead of `LightAWS_Base::load_iam_credentials()`.

## Lightweight AWS Class Hierarchy

```
LightAWS_Base          (includes/lightaws-base.php)
  └── LightAWS_CloudFront  (includes/lightaws-cloudfront.php)
    └── LightAWS_CloudFront_WP  (includes/lightaws-cloudfront-wp.php)

Trait: LightAWS_HTTP_WP_Trait  (includes/lightaws-http-wp-trait.php)
  – optional WP HTTP API transport, not used by the library but added via the trait
```

`LightAWS_Base` handles:
- AWS Signature Version 4 (SigV4) signing via `sign_request()` / `derive_signing_key()`
- Credential resolution: constructor options → env vars → ECS task credentials → IMDSv2 (EC2 instance profile)
- HTTP transport: prefers `curl` when available, falls back to `stream_context_create()` / `file_get_contents()`
- Retry logic (default 3 attempts) with exception re-throw on final failure
- XML response parsing and error extraction

`LightAWS_CloudFront` adds:
- CloudFront-specific endpoint (`https://cloudfront.amazonaws.com`)
- Always signs to `us-east-1` regardless of origin region
- API version: `2020-05-31`
- `createInvalidation(string $distribution_id, array $paths, ?string $caller_reference)`
- `getInvalidation(string $distribution_id, string $invalidation_id)`
- `listInvalidations(string $distribution_id, int $max_items)`

## Adding a New AWS Service

1. Create `includes/lightaws-{service}.php`
2. Extend `LightAWS_Base`
3. Set the correct service name and API version in `__construct()`
4. Implement only the API operations actually needed
5. Use `$this->get()`, `$this->post()`, `$this->put()`, `$this->delete()` for signed requests

## Credential Modes

The `credentials_mode` setting in `frontpup_clear_cache` controls how AWS credentials are resolved:

| Mode | Source | Notes |
|---|---|---|
| `policy` (default) | IAM role / env vars / IMDSv2 | Recommended; no keys stored on server |
| `wpconfig` | `FRONTPUP_ACCESS_KEY_ID` + `FRONTPUP_SECRET_ACCESS_KEY` constants in `wp-config.php` | Keys outside the database |
| `database` | `access_key_id` + `secret_access_key` stored in `frontpup_clear_cache` option | Least preferred |

`LightAWS_Base::load_iam_credentials()` handles `policy` mode automatically for the lightweight and full-SDK paths. The `policy` credential chain order: env vars → ECS task endpoint → IMDSv2. The AsyncAws path uses its own `ChainProvider::createDefaultChain()` instead (env vars → shared credentials/config files → ECS → IMDSv2 → web identity/SSO).

## Cache Invalidation Flow

```
FrontPup_AdminBar::wp_ajax_frontpup_clear_cache_action()
  → FrontPup::get_clear_cache_instance()
    → new FrontPup_Clear_Cache( $settings )
      → FrontPup_Clear_Cache::clear_cache()
        → (lightweight) new LightAWS_CloudFront( $initOptions )
                         →  createInvalidation( $distribution_id, ['/*'] )
        → (asyncaws)    new AsyncAws\CloudFront\CloudFrontClient( $asyncOptions )
                         →  createInvalidation( [...] )
        → (full SDK)    new Aws\CloudFront\CloudFrontClient( $initOptions )
                         →  createInvalidation( [...] )
```

Invalidations always target `/*` (all paths). `CallerReference` is set to `(string) time()` for idempotency.

## CloudFront Region Note

CloudFront is a **global** service. SigV4 signing scope is always `us-east-1` regardless of where the WordPress origin is hosted. `FRONTPUP_REGION` (`us-east-1`) is the default and should not need to change for CloudFront operations.

## Error Handling

- `LightAWS_Base::set_last_error()` stores the message and throws `\Exception` by default (`$EXCEPTIONS_ENABLED = true`).
- Call `LightAWS_Base::disable_exceptions(true)` to switch to a return-value error model and use `get_last_error()` / `get_last_error_code()` instead.
- `FrontPup_Clear_Cache::clear_cache()` catches `\Exception` (or `\Throwable` for the AsyncAws path) and returns `false` on failure; retrieve the message with `get_last_error()`.
- HTTP 4xx/5xx responses are parsed for the CloudFront XML `<ErrorResponse><Error><Message>` envelope before surfacing the error string.
- **AsyncAws only:** `AsyncAws\Core\Result` objects (e.g. `CreateInvalidationResult`) resolve their HTTP response lazily. `$client->createInvalidation(...)` returning does not mean the request succeeded — errors only surface the first time a getter is called on the result. `clear_cache()` calls `$response->getInvalidation()` inside its `try` block specifically to force this resolution before returning, otherwise failures would surface later (e.g. on object destruction) where they can no longer be caught.
