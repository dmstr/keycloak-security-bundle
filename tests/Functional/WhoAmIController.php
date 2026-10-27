<?php
// file generated with AI assistance: Claude Code - 2026-10-27 01:11:25 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Tests\Functional;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Invokable controller behind `GET /api/whoami` in {@see TestKernel}.
 *
 * Returns the identifier and roles of the user on the current security token,
 * so the functional tests can observe what the firewall authenticated.
 */
final class WhoAmIController
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        return new JsonResponse([
            'identifier' => $user?->getUserIdentifier(),
            'roles' => null === $user ? [] : array_values($user->getRoles()),
        ]);
    }
}
