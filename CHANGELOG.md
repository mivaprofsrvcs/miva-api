# Release Notes

## [Unreleased](https://github.com/mivaprofsrvcs/miva-api/compare/v3.0.0...HEAD)

## [v3.0.0](https://github.com/mivaprofsrvcs/miva-api/compare/v2.1.1...v3.0.0) - 2025-12-12
- Migrated HTTP layer to Guzzle 7.10.0 and removed the `pdeans/http` dependency.
- Added SSH authentication with `ssh_auth` options; SSH takes precedence over token auth when configured.
- Added request/response header support: `X-Miva-API-Timeout`, `X-Miva-API-Binary-Encoding`, `Range`, and `Content-Range` parsing for partial (206) responses.
- Expanded client options (`timeout`, `binary_encoding`, `range`, `ssh_auth`) with helper setters.
- Improved response handling with an `ErrorBag`, clearer success/error helpers, and preserved access to mixed iteration/operation results.
- Default headers now include `Accept: application/json` and a versioned `User-Agent`.
- Enforced strict types and PHP 8.3+ across source and tests.
- Added Pest feature test suite, PHPStan (level 8), and Laravel Pint in `--test` mode; introduced GitHub Actions CI running these checks.
