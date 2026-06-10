<?php
// file generated with AI assistance: Claude Code - 2026-03-18

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\PayloadAwareUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Provides user objects from Keycloak JWT payload.
 *
 * Implements PayloadAwareUserProviderInterface so LexikJWT passes the full
 * decoded token payload — allowing us to extract roles, email, and name
 * directly without a separate event listener.
 *
 * @implements PayloadAwareUserProviderInterface<KeycloakUser>
 */
class KeycloakUserProvider implements PayloadAwareUserProviderInterface
{
    public function loadUserByIdentifierAndPayload(string $identifier, array $payload): UserInterface
    {
        return new KeycloakUser(
            identifier: $identifier,
            roles: $this->extractRoles($payload),
            email: $payload['email'] ?? '',
            givenName: $payload['given_name'] ?? '',
            familyName: $payload['family_name'] ?? '',
        );
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Fallback — should not be reached when JWT auth is active
        return new KeycloakUser($identifier);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        // Stateless API — no session refresh needed
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return KeycloakUser::class === $class;
    }

    /**
     * Map Keycloak realm_access.roles to Symfony ROLE_* format.
     *
     * Keycloak roles: ["admin", "user"]
     * Symfony roles:  ["ROLE_ADMIN", "ROLE_USER"]
     */
    private function extractRoles(array $payload): array
    {
        $keycloakRoles = $payload['realm_access']['roles'] ?? [];
        $roles = [];

        foreach ($keycloakRoles as $role) {
            // Skip Keycloak internal roles
            if (str_starts_with($role, 'default-roles-') || $role === 'offline_access' || $role === 'uma_authorization') {
                continue;
            }
            $roles[] = 'ROLE_' . strtoupper($role);
        }

        return $roles;
    }
}
