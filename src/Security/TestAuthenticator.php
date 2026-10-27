<?php
// file generated with AI assistance: Claude Code - 2026-05-30

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Authenticator for the `test` environment only. It bypasses real JWT
 * validation, so functional tests do not need to obtain a Keycloak token.
 *
 * The service is registered only in the `test` environment (see
 * `config/services.yaml`, `when@test`); a reference to it from any other
 * environment fails at container compile time.
 *
 * Without any of the headers below, every request is authenticated as the
 * static admin `test-admin` with `ROLE_ADMIN`, exactly as before. Three
 * request headers steer the behaviour per request:
 *
 * - `X-Test-Auth: anonymous` makes `supports()` return false: no token is
 *   created, and access_control answers 401 via the firewall's entry point.
 * - `X-Test-Roles: ROLE_A, ROLE_B` replaces the roles; the identifier becomes
 *   `test-user` unless the list contains `ROLE_ADMIN`. An empty value means
 *   authenticated with only `ROLE_USER`.
 * - `X-Test-User: alice` replaces the derived identifier, e.g. for ownership
 *   checks with two users.
 *
 * An app wires it with `custom_authenticators` under `when@test` in its
 * `security.yaml`. The bundle deliberately ships no entry point, because
 * Symfony refuses two entry-point authenticators on one firewall without an
 * explicit `entry_point`.
 */
final class TestAuthenticator extends AbstractAuthenticator
{
    public const string HEADER_AUTH = 'X-Test-Auth';
    public const string HEADER_ROLES = 'X-Test-Roles';
    public const string HEADER_USER = 'X-Test-User';
    public const string ADMIN_IDENTIFIER = 'test-admin';
    public const string USER_IDENTIFIER = 'test-user';

    private const string ANONYMOUS = 'anonymous';
    private const string ADMIN_ROLE = 'ROLE_ADMIN';

    public function supports(Request $request): ?bool
    {
        return self::ANONYMOUS !== strtolower(trim((string) $request->headers->get(self::HEADER_AUTH, '')));
    }

    public function authenticate(Request $request): Passport
    {
        $roles = $this->roles($request);
        $isAdmin = in_array(self::ADMIN_ROLE, $roles, true);

        $identifier = trim((string) $request->headers->get(self::HEADER_USER, ''));
        if ('' === $identifier) {
            $identifier = $isAdmin ? self::ADMIN_IDENTIFIER : self::USER_IDENTIFIER;
        }

        return new SelfValidatingPassport(
            new UserBadge($identifier, static fn (string $identifier): KeycloakUser => new KeycloakUser(
                identifier: $identifier,
                roles: $roles,
                email: $isAdmin ? 'admin@test.example.com' : 'user@test.example.com',
                givenName: 'Test',
                familyName: $isAdmin ? 'Admin' : 'User',
            ))
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response('Auth failure: ' . $exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return list<string>
     */
    private function roles(Request $request): array
    {
        if (!$request->headers->has(self::HEADER_ROLES)) {
            return [self::ADMIN_ROLE];
        }

        $roles = array_map('trim', explode(',', (string) $request->headers->get(self::HEADER_ROLES, '')));

        return array_values(array_filter($roles, static fn (string $role): bool => '' !== $role));
    }
}
// revised 2026-10-27 (header-driven test mode)
