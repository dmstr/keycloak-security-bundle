<!-- file generated with AI assistance: Claude Code - 2026-06-09 19:27:00 UTC -->

# dmstr/keycloak-security-bundle

Keycloak-backed Symfony Security.

## Features (planned)

- `KeycloakUser` — user model populated from JWT claims issued by Keycloak
- `KeycloakUserProvider` — Symfony Security user provider
- `TestAuthenticator` — opt-in fake authenticator for tests
- `app:auth:token` CLI — retrieve a JWT for a configured Keycloak user

## RFC 9728 Protected Resource Metadata

Lets clients discover the responsible authorization server knowing nothing
but the API entrypoint ([RFC 9728](https://www.rfc-editor.org/rfc/rfc9728)):

```
GET /.well-known/oauth-protected-resource
{
  "resource": "https://api.example.com",
  "authorization_servers": ["https://kc.example.com/realms/acme"],
  "bearer_methods_supported": ["header"]
}
```

`authorization_servers[0]` is the OIDC issuer — standard OIDC discovery
(`{issuer}/.well-known/openid-configuration`) takes over from there. In
addition, every 401 response advertises the metadata document via the
`WWW-Authenticate` challenge (RFC 9728 section 5.1):

```
WWW-Authenticate: Bearer resource_metadata="https://api.example.com/.well-known/oauth-protected-resource"
```

The feature is opt-in — configure the issuer and import the route:

```yaml
# config/packages/dmstr_keycloak_security.yaml
dmstr_keycloak_security:
    realm_url: '%env(KEYCLOAK_REALM_URL)%'
    # resource: 'https://api.example.com'  # optional, defaults to request scheme+host

# config/routes.yaml
dmstr_keycloak_security:
    resource: '@KeycloakSecurityBundle/config/routes.yaml'
```

The endpoint is public metadata: it responds with `Access-Control-Allow-Origin: *`
and is cacheable (`public, max-age=3600`). Make sure your firewall leaves
`/.well-known/` anonymous.

## License

MIT © diemeisterei GmbH
