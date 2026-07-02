<?php
// file generated with AI assistance: Claude Code - 2026-07-02 19:00:00 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Tests\EventListener;

use Dmstr\KeycloakSecurity\EventListener\ProtectedResourceMetadataListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ProtectedResourceMetadataListenerTest extends TestCase
{
    public function testAddsChallengeWithMetadataUrlOn401(): void
    {
        $event = $this->responseEvent(new Response('', 401));

        (new ProtectedResourceMetadataListener())($event);

        self::assertSame(
            'Bearer resource_metadata="http://api.test/.well-known/oauth-protected-resource"',
            $event->getResponse()->headers->get('WWW-Authenticate'),
        );
    }

    public function testUsesConfiguredResourceAsBase(): void
    {
        $event = $this->responseEvent(new Response('', 401));

        (new ProtectedResourceMetadataListener('https://api.example.com'))($event);

        self::assertSame(
            'Bearer resource_metadata="https://api.example.com/.well-known/oauth-protected-resource"',
            $event->getResponse()->headers->get('WWW-Authenticate'),
        );
    }

    public function testIgnoresNon401Responses(): void
    {
        $event = $this->responseEvent(new Response('', 403));

        (new ProtectedResourceMetadataListener())($event);

        self::assertFalse($event->getResponse()->headers->has('WWW-Authenticate'));
    }

    public function testIgnoresSubRequests(): void
    {
        $event = $this->responseEvent(new Response('', 401), HttpKernelInterface::SUB_REQUEST);

        (new ProtectedResourceMetadataListener())($event);

        self::assertFalse($event->getResponse()->headers->has('WWW-Authenticate'));
    }

    public function testKeepsExistingChallengeThatAlreadyAdvertisesMetadata(): void
    {
        $response = new Response('', 401);
        $response->headers->set('WWW-Authenticate', 'Bearer resource_metadata="https://other.example.com/meta"');
        $event = $this->responseEvent($response);

        (new ProtectedResourceMetadataListener())($event);

        self::assertSame(
            'Bearer resource_metadata="https://other.example.com/meta"',
            $event->getResponse()->headers->get('WWW-Authenticate'),
        );
    }

    private function responseEvent(
        Response $response,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): ResponseEvent {
        return new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('http://api.test/api/admin/jobs'),
            $requestType,
            $response,
        );
    }
}
