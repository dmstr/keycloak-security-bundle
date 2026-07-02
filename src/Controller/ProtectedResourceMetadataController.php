<?php
// file generated with AI assistance: Claude Code - 2026-07-02 19:00:00 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * RFC 9728 OAuth 2.0 Protected Resource Metadata endpoint.
 *
 * Publishes which authorization server protects this API, so clients can
 * bootstrap authentication knowing nothing but the API entrypoint:
 *
 *   GET /.well-known/oauth-protected-resource
 *     → authorization_servers[0]                     (the OIDC issuer)
 *     → {issuer}/.well-known/openid-configuration    (standard OIDC discovery)
 *
 * The document is public metadata (no secrets involved), so CORS is
 * intentionally wide open: browser-based clients on foreign origins must be
 * able to fetch it before any authentication has happened.
 */
final class ProtectedResourceMetadataController
{
    public function __construct(
        private readonly string $realmUrl,
        private readonly ?string $resource = null,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $response = new JsonResponse([
            'resource' => $this->resource ?? $request->getSchemeAndHttpHost(),
            'authorization_servers' => [$this->realmUrl],
            'bearer_methods_supported' => ['header'],
        ]);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
