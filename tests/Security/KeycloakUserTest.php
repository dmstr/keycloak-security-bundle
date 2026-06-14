<?php
// file generated with AI assistance: Claude Code - 2026-06-13 23:14:54 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Tests\Security;

use Dmstr\KeycloakSecurity\Security\KeycloakUser;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see KeycloakUser} — the immutable DTO wrapping a Keycloak
 * JWT identity. Verifies the mandatory ROLE_USER guarantee, deduplication and
 * the plain getters.
 */
final class KeycloakUserTest extends TestCase
{
    public function testGetRolesAlwaysAddsRoleUser(): void
    {
        $user = new KeycloakUser('alice', ['ROLE_ADMIN']);

        $roles = array_values($user->getRoles());
        self::assertContains('ROLE_ADMIN', $roles);
        self::assertContains('ROLE_USER', $roles);
    }

    public function testGetRolesDeduplicatesRoleUser(): void
    {
        $user = new KeycloakUser('alice', ['ROLE_USER', 'ROLE_ADMIN']);

        self::assertSame(
            ['ROLE_USER', 'ROLE_ADMIN'],
            array_values($user->getRoles()),
            'ROLE_USER must not be duplicated when already present.'
        );
    }

    public function testEmptyRolesYieldOnlyRoleUser(): void
    {
        self::assertSame(['ROLE_USER'], array_values((new KeycloakUser('alice'))->getRoles()));
    }

    public function testGettersExposeConstructorValues(): void
    {
        $user = new KeycloakUser('alice', [], 'alice@example.com', 'Alice', 'Anderson');

        self::assertSame('alice', $user->getUserIdentifier());
        self::assertSame('alice@example.com', $user->getEmail());
        self::assertSame('Alice', $user->getGivenName());
        self::assertSame('Anderson', $user->getFamilyName());
    }

    public function testGettersDefaultToEmptyStrings(): void
    {
        $user = new KeycloakUser('alice');

        self::assertSame('', $user->getEmail());
        self::assertSame('', $user->getGivenName());
        self::assertSame('', $user->getFamilyName());
    }

    public function testEraseCredentialsIsNoop(): void
    {
        $user = new KeycloakUser('alice', ['ROLE_ADMIN'], 'alice@example.com');
        $user->eraseCredentials();

        self::assertSame('alice', $user->getUserIdentifier());
        self::assertSame('alice@example.com', $user->getEmail());
    }
}
