<?php
// file generated with AI assistance: Claude Code - 2026-05-30

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Authenticator used only in the `test` environment to bypass real JWT
 * validation against the `api` firewall. Every request under /api/admin
 * is authenticated as a static admin user, so functional tests do not
 * need to obtain a real Keycloak token.
 *
 * Wired up via app/config/packages/security.yaml under `when@test`.
 */
final class TestAuthenticator extends AbstractAuthenticator
{
    private const TEST_USER_IDENTIFIER = 'test-admin';

    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        return new SelfValidatingPassport(
            new UserBadge(self::TEST_USER_IDENTIFIER, fn (string $identifier) => new KeycloakUser(
                identifier: $identifier,
                roles: ['ROLE_ADMIN'],
                email: 'admin@test.example.com',
                givenName: 'Test',
                familyName: 'Admin',
            ))
        );
    }

    public function onAuthenticationSuccess(Request $request, mixed $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response('Auth failure: ' . $exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }
}
