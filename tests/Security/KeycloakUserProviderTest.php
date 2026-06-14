<?php
// file generated with AI assistance: Claude Code - 2026-06-13 23:14:54 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Tests\Security;

use Dmstr\KeycloakSecurity\Security\KeycloakUser;
use Dmstr\KeycloakSecurity\Security\KeycloakUserProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see KeycloakUserProvider}.
 *
 * Exercises the realm_access.roles → ROLE_* mapping (uppercasing, filtering of
 * Keycloak-internal roles) and claim extraction via the public
 * loadUserByIdentifierAndPayload() entry point.
 */
final class KeycloakUserProviderTest extends TestCase
{
    private KeycloakUserProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new KeycloakUserProvider();
    }

    public function testMapsRealmRolesToUppercasedSymfonyRoles(): void
    {
        $user = $this->provider->loadUserByIdentifierAndPayload('alice', [
            'realm_access' => ['roles' => ['admin', 'editor']],
        ]);

        $roles = array_values($user->getRoles());
        self::assertContains('ROLE_ADMIN', $roles);
        self::assertContains('ROLE_EDITOR', $roles);
        self::assertContains('ROLE_USER', $roles);
    }

    public function testFiltersKeycloakInternalRoles(): void
    {
        $user = $this->provider->loadUserByIdentifierAndPayload('alice', [
            'realm_access' => ['roles' => [
                'admin',
                'default-roles-myrealm',
                'offline_access',
                'uma_authorization',
            ]],
        ]);

        // Only ROLE_ADMIN survives the filter; ROLE_USER is added by the user.
        self::assertEqualsCanonicalizing(['ROLE_ADMIN', 'ROLE_USER'], array_values($user->getRoles()));
    }

    public function testExtractsEmailAndName(): void
    {
        $user = $this->provider->loadUserByIdentifierAndPayload('alice', [
            'email' => 'alice@example.com',
            'given_name' => 'Alice',
            'family_name' => 'Anderson',
        ]);

        self::assertInstanceOf(KeycloakUser::class, $user);
        self::assertSame('alice', $user->getUserIdentifier());
        self::assertSame('alice@example.com', $user->getEmail());
        self::assertSame('Alice', $user->getGivenName());
        self::assertSame('Anderson', $user->getFamilyName());
    }

    public function testMissingClaimsDefaultToEmpty(): void
    {
        $user = $this->provider->loadUserByIdentifierAndPayload('alice', []);

        self::assertSame('', $user->getEmail());
        self::assertSame('', $user->getGivenName());
        self::assertSame('', $user->getFamilyName());
        self::assertSame(['ROLE_USER'], array_values($user->getRoles()));
    }

    public function testLoadUserByIdentifierFallbackHasNoRealmRoles(): void
    {
        $user = $this->provider->loadUserByIdentifier('alice');

        self::assertSame('alice', $user->getUserIdentifier());
        self::assertSame(['ROLE_USER'], array_values($user->getRoles()));
    }

    public function testRefreshUserReturnsSameInstance(): void
    {
        $user = new KeycloakUser('alice', ['ROLE_ADMIN']);
        self::assertSame($user, $this->provider->refreshUser($user));
    }

    public function testSupportsOnlyKeycloakUserClass(): void
    {
        self::assertTrue($this->provider->supportsClass(KeycloakUser::class));
        self::assertFalse($this->provider->supportsClass(\stdClass::class));
    }
}
