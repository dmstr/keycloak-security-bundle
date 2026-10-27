<?php
// file generated with AI assistance: Claude Code - 2026-10-27 01:11:25 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Tests\Functional;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Explicit firewall entry point for the `entry_point` variant of
 * {@see TestKernel}.
 *
 * Answers with a recognisable 401 body, so a test can tell that an anonymous
 * request reached this entry point and not some other one.
 */
final class TestEntryPoint implements AuthenticationEntryPointInterface
{
    public const string BODY = 'custom-entry-point';

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new Response(self::BODY, Response::HTTP_UNAUTHORIZED);
    }
}
