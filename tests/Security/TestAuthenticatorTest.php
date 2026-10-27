<?php
// file generated with AI assistance: Claude Code - 2026-10-27 01:11:25 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Tests\Security;

use Dmstr\KeycloakSecurity\Security\KeycloakUser;
use Dmstr\KeycloakSecurity\Security\TestAuthenticator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Unit tests for {@see TestAuthenticator}.
 *
 * Covers the header contract (`X-Test-Auth`, `X-Test-Roles`, `X-Test-User`):
 * which requests are supported, which identifier, roles and claims the
 * resulting {@see KeycloakUser} carries, the success/failure callbacks, and
 * the structural promises (final, no constructor, no entry point, constants).
 */
final class TestAuthenticatorTest extends TestCase
{
    private TestAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->authenticator = new TestAuthenticator();
    }

    public function testNoHeadersAuthenticateAsAdmin(): void
    {
        $request = $this->request([]);
        self::assertTrue($this->authenticator->supports($request));

        $user = $this->user($request);
        self::assertSame('test-admin', $user->getUserIdentifier());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], array_values($user->getRoles()));
        self::assertSame('admin@test.example.com', $user->getEmail());
        self::assertSame('Test', $user->getGivenName());
        self::assertSame('Admin', $user->getFamilyName());
    }

    public function testRolesHeaderWithoutAdminAuthenticatesAsUser(): void
    {
        $user = $this->user($this->request(['HTTP_X_TEST_ROLES' => 'ROLE_FOO']));

        self::assertSame('test-user', $user->getUserIdentifier());
        self::assertSame(['ROLE_FOO', 'ROLE_USER'], array_values($user->getRoles()));
        self::assertSame('user@test.example.com', $user->getEmail());
        self::assertSame('Test', $user->getGivenName());
        self::assertSame('User', $user->getFamilyName());
    }

    /**
     * @param list<string> $expectedRoles
     */
    #[DataProvider('rolesHeaders')]
    public function testRolesHeaderIsParsed(string $header, string $expectedIdentifier, array $expectedRoles): void
    {
        $user = $this->user($this->request(['HTTP_X_TEST_ROLES' => $header]));

        self::assertSame($expectedIdentifier, $user->getUserIdentifier());
        self::assertSame($expectedRoles, array_values($user->getRoles()));
    }

    /**
     * @return iterable<string, array{string, string, list<string>}>
     */
    public static function rolesHeaders(): iterable
    {
        yield 'whitespace and trailing comma' => [' ROLE_FOO , ROLE_BAR ,', 'test-user', ['ROLE_FOO', 'ROLE_BAR', 'ROLE_USER']];
        yield 'empty' => ['', 'test-user', ['ROLE_USER']];
        yield 'only commas and blanks' => [' , ,', 'test-user', ['ROLE_USER']];
        yield 'admin among others' => ['ROLE_FOO,ROLE_ADMIN', 'test-admin', ['ROLE_FOO', 'ROLE_ADMIN', 'ROLE_USER']];
        yield 'explicit ROLE_USER once' => ['ROLE_USER', 'test-user', ['ROLE_USER']];
    }

    public function testAdminAmongOtherRolesGetsAdminClaims(): void
    {
        $user = $this->user($this->request(['HTTP_X_TEST_ROLES' => 'ROLE_FOO,ROLE_ADMIN']));

        self::assertSame('admin@test.example.com', $user->getEmail());
        self::assertSame('Admin', $user->getFamilyName());
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('supportCases')]
    public function testSupportsDependsOnAuthHeader(array $server, bool $expected): void
    {
        self::assertSame($expected, $this->authenticator->supports($this->request($server)));
    }

    /**
     * @return iterable<string, array{array<string, string>, bool}>
     */
    public static function supportCases(): iterable
    {
        yield 'absent' => [[], true];
        yield 'anonymous' => [['HTTP_X_TEST_AUTH' => 'anonymous'], false];
        yield 'Anonymous' => [['HTTP_X_TEST_AUTH' => 'Anonymous'], false];
        yield 'padded anonymous' => [['HTTP_X_TEST_AUTH' => ' anonymous '], false];
        yield 'foo' => [['HTTP_X_TEST_AUTH' => 'foo'], true];
        yield 'anonymous with roles' => [['HTTP_X_TEST_AUTH' => 'anonymous', 'HTTP_X_TEST_ROLES' => 'ROLE_ADMIN'], false];
    }

    public function testUserHeaderReplacesIdentifier(): void
    {
        $user = $this->user($this->request([
            'HTTP_X_TEST_USER' => 'alice',
            'HTTP_X_TEST_ROLES' => 'ROLE_FOO',
        ]));

        self::assertSame('alice', $user->getUserIdentifier());
        self::assertSame(['ROLE_FOO', 'ROLE_USER'], array_values($user->getRoles()));
        self::assertSame('user@test.example.com', $user->getEmail());
    }

    #[DataProvider('blankUserHeaders')]
    public function testBlankUserHeaderFallsBackToDerivedIdentifier(string $header): void
    {
        self::assertSame('test-admin', $this->user($this->request(['HTTP_X_TEST_USER' => $header]))->getUserIdentifier());
        self::assertSame('test-user', $this->user($this->request([
            'HTTP_X_TEST_USER' => $header,
            'HTTP_X_TEST_ROLES' => 'ROLE_FOO',
        ]))->getUserIdentifier());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function blankUserHeaders(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace' => ['   '];
    }

    public function testStructure(): void
    {
        $class = new \ReflectionClass(TestAuthenticator::class);

        self::assertTrue($class->isFinal());
        self::assertNull($class->getConstructor());
        self::assertFalse($class->implementsInterface(AuthenticationEntryPointInterface::class));
    }

    public function testPublicConstants(): void
    {
        self::assertSame('X-Test-Auth', TestAuthenticator::HEADER_AUTH);
        self::assertSame('X-Test-Roles', TestAuthenticator::HEADER_ROLES);
        self::assertSame('X-Test-User', TestAuthenticator::HEADER_USER);
        self::assertSame('test-admin', TestAuthenticator::ADMIN_IDENTIFIER);
        self::assertSame('test-user', TestAuthenticator::USER_IDENTIFIER);
    }

    public function testOnAuthenticationSuccessLetsTheRequestContinue(): void
    {
        self::assertNull($this->authenticator->onAuthenticationSuccess(
            $this->request([]),
            $this->createStub(TokenInterface::class),
            'api',
        ));
    }

    public function testOnAuthenticationFailureAnswers401(): void
    {
        $response = $this->authenticator->onAuthenticationFailure(
            $this->request([]),
            new AuthenticationException('nope'),
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @param array<string, string> $server
     */
    private function request(array $server): Request
    {
        return Request::create('/api/x', 'GET', server: $server);
    }

    private function user(Request $request): KeycloakUser
    {
        $user = $this->authenticator->authenticate($request)->getBadge(UserBadge::class)->getUser();
        self::assertInstanceOf(KeycloakUser::class, $user);

        return $user;
    }
}
