<?php
// file generated with AI assistance: Claude Code - 2026-07-02 19:00:00 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity;

use Dmstr\KeycloakSecurity\Controller\ProtectedResourceMetadataController;
use Dmstr\KeycloakSecurity\EventListener\ProtectedResourceMetadataListener;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class KeycloakSecurityBundle extends AbstractBundle
{
    protected string $extensionAlias = 'dmstr_keycloak_security';

    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $container->import(__DIR__ . '/../config/services.yaml');

        // RFC 9728 protected-resource metadata is opt-in: it only makes sense
        // when the app tells us which realm (issuer) protects the API.
        $realmUrl = $config['realm_url'] ?? null;
        if (null === $realmUrl || '' === $realmUrl) {
            return;
        }

        $services = $container->services();
        $services->set(ProtectedResourceMetadataController::class)
            ->arg('$realmUrl', $realmUrl)
            ->arg('$resource', $config['resource'])
            ->public()
            ->tag('controller.service_arguments');
        $services->set(ProtectedResourceMetadataListener::class)
            ->arg('$resource', $config['resource'])
            ->tag('kernel.event_listener', ['event' => 'kernel.response']);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('realm_url')
                    ->defaultNull()
                    ->info('Keycloak realm URL / OIDC issuer, e.g. "https://kc.example.com/realms/acme". Setting it enables the RFC 9728 protected-resource-metadata endpoint (see config/routes.yaml) and the WWW-Authenticate discovery hint on 401 responses.')
                ->end()
                ->scalarNode('resource')
                    ->defaultNull()
                    ->info('RFC 9728 resource identifier of this API. Defaults to the scheme+host of the incoming request.')
                ->end()
            ->end();
    }
}
