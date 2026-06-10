<?php
// file generated with AI assistance: Claude Code - 2026-03-16

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class KeycloakUser implements UserInterface
{
    private string $identifier;
    private array $roles;
    private string $email;
    private string $givenName;
    private string $familyName;

    public function __construct(
        string $identifier,
        array $roles = [],
        string $email = '',
        string $givenName = '',
        string $familyName = '',
    ) {
        $this->identifier = $identifier;
        $this->roles = $roles;
        $this->email = $email;
        $this->givenName = $givenName;
        $this->familyName = $familyName;
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Symfony requires ROLE_USER for all authenticated users
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getGivenName(): string
    {
        return $this->givenName;
    }

    public function getFamilyName(): string
    {
        return $this->familyName;
    }

    public function eraseCredentials(): void
    {
        // No credentials stored locally
    }
}
