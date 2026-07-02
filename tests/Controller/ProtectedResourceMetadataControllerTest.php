<?php
// file generated with AI assistance: Claude Code - 2026-07-02 19:00:00 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Tests\Controller;

use Dmstr\KeycloakSecurity\Controller\ProtectedResourceMetadataController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ProtectedResourceMetadataControllerTest extends TestCase
{
    private const string REALM_URL = 'http://keycloak.test/realms/acme';

    public function testMetadataDerivesResourceFromRequest(): void
    {
        $controller = new ProtectedResourceMetadataController(self::REALM_URL);

        $request = Request::create('http://api.test:8080/.well-known/oauth-protected-resource');
        $payload = json_decode((string) $controller($request)->getContent(), true);

        self::assertSame('http://api.test:8080', $payload['resource']);
        self::assertSame([self::REALM_URL], $payload['authorization_servers']);
        self::assertSame(['header'], $payload['bearer_methods_supported']);
    }

    public function testExplicitResourceOverridesRequestDerivation(): void
    {
        $controller = new ProtectedResourceMetadataController(self::REALM_URL, 'https://api.example.com');

        $request = Request::create('http://internal-host/.well-known/oauth-protected-resource');
        $payload = json_decode((string) $controller($request)->getContent(), true);

        self::assertSame('https://api.example.com', $payload['resource']);
    }

    public function testResponseIsCorsOpenAndCacheable(): void
    {
        $controller = new ProtectedResourceMetadataController(self::REALM_URL);

        $response = $controller(Request::create('http://api.test/.well-known/oauth-protected-resource'));

        self::assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        self::assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
    }
}
