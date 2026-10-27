<?php
// file generated with AI assistance: Claude Code - 2026-10-27 01:11:25 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Tests\Functional;

use Dmstr\KeycloakSecurity\KeycloakSecurityBundle;
use Dmstr\KeycloakSecurity\Security\KeycloakUserProvider;
use Dmstr\KeycloakSecurity\Security\TestAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Minimal kernel for the functional tests of {@see TestAuthenticator}.
 *
 * Registers FrameworkBundle, SecurityBundle, LexikJWTAuthenticationBundle and
 * the bundle itself, so the real `config/services.yaml` (including its
 * `when@test` guard) decides whether the authenticator service exists. The
 * `api` firewall references the authenticator in every environment; in any
 * environment other than `test` the container therefore fails to compile.
 *
 * The variant selects the entry point of the firewall: `jwt` adds the Lexik
 * JWT authenticator (which becomes the entry point), `entry_point` sets
 * {@see TestEntryPoint} explicitly instead.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public const string VARIANT_JWT = 'jwt';
    public const string VARIANT_ENTRY_POINT = 'entry_point';

    public function __construct(string $environment, bool $debug, private readonly string $variant)
    {
        if (!in_array($variant, [self::VARIANT_JWT, self::VARIANT_ENTRY_POINT], true)) {
            throw new \InvalidArgumentException(sprintf('Unknown firewall variant "%s".', $variant));
        }

        parent::__construct($environment, $debug);
    }

    public static function baseDir(string $environment, string $variant): string
    {
        return sys_get_temp_dir() . '/dmstr-keycloak-security-bundle/' . $environment . '-' . $variant;
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SecurityBundle();
        yield new LexikJWTAuthenticationBundle();
        yield new KeycloakSecurityBundle();
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Overrides the MicroKernelTrait default (`<project dir>/config`), which
     * would be the bundle's own `config/`: in debug mode FrameworkBundle dumps
     * `reference.php` there. The directory below is never created, so nothing
     * is written; container and routes are configured in code anyway.
     */
    private function getConfigDir(): string
    {
        return self::baseDir($this->environment, $this->variant) . '/config';
    }

    public function getCacheDir(): string
    {
        return self::baseDir($this->environment, $this->variant) . '/cache';
    }

    public function getLogDir(): string
    {
        return self::baseDir($this->environment, $this->variant) . '/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test-secret',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'router' => ['utf8' => true],
        ]);

        // Lexik insists on one of the two keys. The path does not exist on
        // purpose: neither the entry point nor supports() loads a key.
        $container->extension('lexik_jwt_authentication', [
            'public_key' => '%kernel.project_dir%/tests/Functional/missing-public-key.pem',
        ]);

        $firewall = [
            'pattern' => '^/api',
            'stateless' => true,
            'provider' => 'keycloak',
            'custom_authenticators' => [TestAuthenticator::class],
        ];
        if (self::VARIANT_JWT === $this->variant) {
            $firewall['jwt'] = null;
        } else {
            $firewall['entry_point'] = TestEntryPoint::class;
        }

        $container->extension('security', [
            'providers' => [
                'keycloak' => ['id' => KeycloakUserProvider::class],
            ],
            'firewalls' => [
                'api' => $firewall,
            ],
            'access_control' => [
                ['path' => '^/api', 'roles' => 'ROLE_USER'],
            ],
        ]);

        $services = $container->services();
        $services->set(WhoAmIController::class)
            ->autowire()
            ->tag('controller.service_arguments')
            ->public();
        $services->set(TestEntryPoint::class);
        // Keeps the default stderr logger of HttpKernel out of the PHPUnit output.
        $services->set('logger', NullLogger::class);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('whoami', '/api/whoami')
            ->controller(WhoAmIController::class)
            ->methods(['GET']);
    }
}
