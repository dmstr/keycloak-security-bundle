<?php
// file generated with AI assistance: Claude Code - 2026-10-27 01:11:25 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Tests\Functional;

use Dmstr\KeycloakSecurity\Security\TestAuthenticator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for {@see TestAuthenticator} behind a real Symfony firewall.
 *
 * Boots {@see TestKernel} with the bundle registered and sends requests to
 * `GET /api/whoami`. Covers the header-driven identities, the 401 answer of
 * both entry-point flavours for anonymous requests, and the `when@test`
 * guard: outside the `test` environment the authenticator service does not
 * exist and the container fails to compile.
 */
final class TestAuthenticatorFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        foreach (['test', 'prod', 'dev'] as $environment) {
            foreach ([TestKernel::VARIANT_JWT, TestKernel::VARIANT_ENTRY_POINT] as $variant) {
                (new Filesystem())->remove(TestKernel::baseDir($environment, $variant));
            }
        }
    }

    public function testNoHeadersAuthenticateAsAdmin(): void
    {
        $response = $this->request(TestKernel::VARIANT_JWT, []);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(
            ['identifier' => 'test-admin', 'roles' => ['ROLE_ADMIN', 'ROLE_USER']],
            $this->json($response),
        );
    }

    public function testRolesHeaderAuthenticatesAsUser(): void
    {
        $response = $this->request(TestKernel::VARIANT_JWT, ['HTTP_X_TEST_ROLES' => 'ROLE_FOO']);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(
            ['identifier' => 'test-user', 'roles' => ['ROLE_FOO', 'ROLE_USER']],
            $this->json($response),
        );
    }

    public function testUserHeaderReplacesIdentifier(): void
    {
        $response = $this->request(TestKernel::VARIANT_JWT, [
            'HTTP_X_TEST_USER' => 'alice',
            'HTTP_X_TEST_ROLES' => 'ROLE_FOO',
        ]);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(
            ['identifier' => 'alice', 'roles' => ['ROLE_FOO', 'ROLE_USER']],
            $this->json($response),
        );
    }

    public function testEmptyRolesHeaderStillSatisfiesRoleUser(): void
    {
        $response = $this->request(TestKernel::VARIANT_JWT, ['HTTP_X_TEST_ROLES' => '']);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(
            ['identifier' => 'test-user', 'roles' => ['ROLE_USER']],
            $this->json($response),
        );
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('anonymousRequests')]
    public function testAnonymousRequestGetsLexikFailureResponse(array $server): void
    {
        $response = $this->request(TestKernel::VARIANT_JWT, $server);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertStringStartsWith('Bearer', (string) $response->headers->get('WWW-Authenticate'));
        $body = $this->json($response);
        self::assertArrayHasKey('message', $body);
        self::assertStringContainsString('JWT Token not found', $body['message']);
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('anonymousRequests')]
    public function testAnonymousRequestGetsExplicitEntryPoint(array $server): void
    {
        $response = $this->request(TestKernel::VARIANT_ENTRY_POINT, $server);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(TestEntryPoint::BODY, $response->getContent());
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function anonymousRequests(): iterable
    {
        yield 'anonymous' => [['HTTP_X_TEST_AUTH' => 'anonymous']];
        yield 'anonymous with roles' => [['HTTP_X_TEST_AUTH' => 'anonymous', 'HTTP_X_TEST_ROLES' => 'ROLE_ADMIN']];
    }

    #[DataProvider('variants')]
    public function testVariantBootsInTestEnvironment(string $variant): void
    {
        $kernel = new TestKernel('test', true, $variant);
        $kernel->boot();

        try {
            self::assertTrue($kernel->getContainer()->has(TestAuthenticator::class));
        } finally {
            $kernel->shutdown();
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function variants(): iterable
    {
        yield 'jwt' => [TestKernel::VARIANT_JWT];
        yield 'entry_point' => [TestKernel::VARIANT_ENTRY_POINT];
    }

    #[DataProvider('foreignEnvironments')]
    public function testAuthenticatorIsMissingOutsideTestEnvironment(string $environment, bool $debug): void
    {
        $kernel = new TestKernel($environment, $debug, TestKernel::VARIANT_JWT);

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(TestAuthenticator::class);

        try {
            $kernel->boot();
        } finally {
            $kernel->shutdown();
        }
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function foreignEnvironments(): iterable
    {
        yield 'prod' => ['prod', false];
        yield 'dev' => ['dev', true];
    }

    /**
     * @param array<string, string> $server
     */
    private function request(string $variant, array $server): Response
    {
        $kernel = new TestKernel('test', true, $variant);
        $kernel->boot();

        try {
            return $kernel->handle(Request::create('/api/whoami', 'GET', server: $server));
        } finally {
            $kernel->shutdown();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function json(Response $response): array
    {
        $data = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }
}
