<!-- file generated with AI assistance: Claude Code - 2026-10-27 01:07:42 UTC -->

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.4.0] - 2026-10-27

### Added

- Header-driven test mode for `TestAuthenticator`: `X-Test-Roles` (comma-separated roles, replaces `ROLE_ADMIN`; an empty value means only `ROLE_USER`), `X-Test-User` (overrides the identifier) and `X-Test-Auth: anonymous` (the authenticator steps back and the firewall answers 401 via its entry point). Without these headers the behaviour is unchanged (static `test-admin` with `ROLE_ADMIN`). The header names are public constants on the class.
- Unit tests and a functional test with a minimal Symfony test kernel, covering roles, anonymous mode via both entry-point paths, and the test-only guard.

### Changed

- **Breaking for mis-wired applications:** `TestAuthenticator` is registered as a service only in the `test` environment (a `when@test` block in `config/services.yaml`). An application that references it in another environment now fails at container compile time with `ServiceNotFoundException`. Wire it only under `when@test`.
- `TestAuthenticator::onAuthenticationSuccess()` now type-hints `TokenInterface $token` instead of `mixed`.
- CI (GitHub Actions) and the local Docker Compose test runner run on PHP 8.5; the package still requires PHP >= 8.4.
- The README feature list is brought up to date, and a new "Test mode" section describes the `TestAuthenticator` headers and wiring.
- The `CLAUDE.md` language policy also covers communication about the repository (merge requests, chat).

## [0.3.0] - 2026-07-03

### Added

- RFC 9728 protected resource metadata endpoint (`/.well-known/oauth-protected-resource`, opt-in via `dmstr_keycloak_security.realm_url`) and a `WWW-Authenticate` discovery hint on 401 responses.

## [0.2.0] - 2026-06-14

### Added

- PHPUnit unit tests, a CLI-only Docker Compose test runner, a GitHub Actions workflow and `CLAUDE.md` with the English-only policy.

### Changed

- **Breaking:** the minimum PHP version is raised to PHP >= 8.4.

## [0.1.0] - 2026-06-10

### Added

- Initial release: `KeycloakUser`, `KeycloakUserProvider`, `TestAuthenticator` and the `app:auth:token` CLI command.

[0.4.0]: https://github.com/dmstr/keycloak-security-bundle/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/dmstr/keycloak-security-bundle/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/dmstr/keycloak-security-bundle/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/dmstr/keycloak-security-bundle/releases/tag/0.1.0
