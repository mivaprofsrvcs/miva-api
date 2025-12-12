# MVPS Miva API - Agent Guidelines

These guidelines define how agent models (Codex or otherwise) should interact with and modify the **MVPS Miva API** library. They ensure consistency, predictability, and high-quality output aligned with modern PHP package development standards. All changes must adhere to these rules unless explicitly instructed otherwise.

---

## Foundational Context

This repository is a modern PHP package built for interacting with the **Miva JSON API**. The project uses:

* **PHP**: 8.3+
* **Guzzle**: 7.10.0+
* **Pest**: 4+
* **PHPStan**: Level 8
* **Laravel Pint**: PSR mode, executed with `--test`
* **declare(strict_types=1);** in all PHP source files

Agents should assume deep expertise with:

* PSR-4 autoloading
* PSR-12 coding standards
* HTTP client design
* API authentication patterns
* Error-handling and response abstraction
* Test-driven development with Pest
* Static analysis and CI workflows

---

## Core Conventions

### Code Structure

* Follow the existing directory structure. Do not introduce new root-level folders unless explicitly instructed.
* When modifying a class, review sibling classes for naming, style, and architectural consistency.
* Use clear, descriptive method and property names. Example: `buildHeaders`, not `hdrs()`.

### Type Safety

* All PHP files must begin with:

  ```php
  declare(strict_types=1);
  ```

* Always specify parameter and return types.

* Use array shapes in PHPDoc when appropriate.

### Comments and Documentation

* Use PHPDoc blocks for all public methods and complex internal methods.
* Keep inline comments minimal and only when they add meaningful context.
* Do not generate redundant docblocks or comments.

---

## Response and Request Architecture

Agents must follow these principles when modifying or extending request/response handling:

### Authentication

* All authentication (token or SSH) must generate the correct `X-Miva-API-Authorization` header.
* SSH authentication takes precedence when both SSH and token credentials are provided.
* Authentication configuration should be validated early and predictably.

### Response Handling

* Single-function responses use a top-level `success` integer. This maps directly to `success()` and `failed()` helpers.
* Multi-call responses (iterations or operations) require:

  * Helpers to detect partial failures
  * Clear distinction between *request failure* and *response-level errors*
* Error structures should use a dedicated immutable "Bag"-style error class.

### Consistency

* Any new helpers must align with the tone and style of existing helpers.
* Follow Laravel-like naming conventions where reasonable.

---

## Testing Expectations

Agents modifying or creating code must ensure:

### Test Suite Standards

* Tests are written using **Pest**.
* Prefer **feature tests** over low-level unit tests unless otherwise required.
* Cover:

  * Client proxy methods (`count`, `filter`, `filters`, `odc`, `offset`, `params`, `passphrase`, `sort`)
  * Token and SSH authentication behavior
  * Header generation, including timeout, binary encoding, and range headers
  * Response parsing logic across *all* known Miva response shapes

### Live Tests

* The `SshAuthLiveTest` must remain intact.
* Live tests must only run when explicitly enabled via environment variables.

---

## Tooling Requirements

### PHPStan

* Must run at **level 8**.
* Any introduced type violations must be fixed, not suppressed.

### Pint

* Must run in `--test` mode.
* Pint should *not* auto-fix formatting.
* Agents must write PSR-compliant code.

### CI

* Agents should assume CI runs:

  * `composer test`
  * `composer phpstan`
  * `composer pint:test`
* Agents must not output CI workflow files unless requested.

---

## Behavioral Requirements for Agents

### Precision

* Provide exact changes rather than general advice.
* Do not generate placeholder implementations unless explicitly asked.

### Restraint

* Do not remove functionality unless the user requests its removal.
* Do not introduce breaking changes unless the task explicitly authorizes it.
* Preserve backwards compatibility as much as possible.

### Clarity

* When rewriting methods, ensure readability and maintainability remain high.
* Avoid overly clever code.

### Error Prevention

* Validate assumptions.
* Highlight potential impacts of architectural changes.
* Ensure generated code passes static analysis and stylistic checks.

---

## Documentation Guidelines

* Update `readme.md` only when explicitly tasked.
* Keep documentation practical, concise, and accurate.
* Include examples only when they add clarity.

---

## Decision Rules

When making a judgment call, agents should:

1. Follow existing patterns in the codebase.
2. Choose clarity over cleverness.
3. Favor explicit, predictable behavior.
4. Maintain backward compatibility unless explicitly directed otherwise.

---

## Summary

These guidelines ensure that all automated modifications to the MVPS Miva API package remain:

* Consistent
* Predictable
* High-quality
* Easy to maintain

Agents must use these rules as their foundation when generating or modifying any part of the library.
