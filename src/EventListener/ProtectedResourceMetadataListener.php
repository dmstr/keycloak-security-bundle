<?php
// file generated with AI assistance: Claude Code - 2026-07-02 19:00:00 UTC

declare(strict_types=1);

namespace Dmstr\KeycloakSecurity\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Advertises the RFC 9728 protected-resource-metadata document on 401
 * responses via the WWW-Authenticate challenge (RFC 9728 section 5.1):
 *
 *   WWW-Authenticate: Bearer resource_metadata="https://api.example.com/.well-known/oauth-protected-resource"
 *
 * A client that only knows the API entrypoint can follow this pointer to
 * discover the responsible authorization server after the first rejected
 * request — no out-of-band IdP configuration needed.
 */
final class ProtectedResourceMetadataListener
{
    public const string WELL_KNOWN_PATH = '/.well-known/oauth-protected-resource';

    public function __construct(
        private readonly ?string $resource = null,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        if (401 !== $response->getStatusCode()) {
            return;
        }

        // Keep a challenge that already advertises metadata (e.g. set by a
        // more specific authenticator) — only fill the gap.
        if (str_contains((string) $response->headers->get('WWW-Authenticate'), 'resource_metadata')) {
            return;
        }

        $base = $this->resource ?? $event->getRequest()->getSchemeAndHttpHost();
        $response->headers->set(
            'WWW-Authenticate',
            \sprintf('Bearer resource_metadata="%s"', $base.self::WELL_KNOWN_PATH),
        );
    }
}
