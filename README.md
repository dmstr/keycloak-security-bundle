<!-- file generated with AI assistance: Claude Code - 2026-06-09 19:27:00 UTC -->

# dmstr/keycloak-security-bundle

Keycloak-backed Symfony Security.

## Features

- `KeycloakUser` — user model populated from the JWT claims issued by Keycloak
- `KeycloakUserProvider` — Symfony Security user provider; maps the Keycloak
  `realm_access.roles` claim to `ROLE_*` roles
- `TestAuthenticator` — header-driven authenticator for functional tests,
  available only in the `test` environment (see
  [Test mode](#test-mode-testauthenticator))
- `app:auth:token` CLI — retrieves a JWT for a configured Keycloak user
- RFC 9728 protected resource metadata endpoint (see
  [RFC 9728 Protected Resource Metadata](#rfc-9728-protected-resource-metadata))

## Test mode (TestAuthenticator)

Functional tests of a consuming API should not need a running Keycloak or a
real token. `Dmstr\KeycloakSecurity\Security\TestAuthenticator` authenticates
requests without any JWT validation for that purpose. The bundle registers
this service **only in the `test` environment** (a `when@test` block in the
bundle's `config/services.yaml`). A reference to it from `dev` or `prod`
therefore fails at container compile time with a `ServiceNotFoundException`,
instead of silently authenticating every request as an administrator.

> **Warning:** never wire `TestAuthenticator` outside a `when@test` block. It
> accepts every request without checking any credential.

### Wiring

Add the authenticator to your API firewall in the application's
`config/packages/security.yaml`, inside `when@test`:

```yaml
when@test:
    security:
        firewalls:
            api:
                custom_authenticators:
                    - Dmstr\KeycloakSecurity\Security\TestAuthenticator
```

Under `when@test` the firewall definition is merged into the base definition
of the `api` firewall, so `jwt: ~` (or an `entry_point`) of the base firewall
stays in place and the test authenticator is added next to the JWT
authenticator. The test authenticator supports every request unless the
anonymous header (see below) is set. The Lexik JWT authenticator, in contrast,
only supports requests that carry an `Authorization: Bearer` header, so a test
request without a token is handled by the test authenticator alone. A request
that does send a bearer token still goes through the JWT authenticator as
well, so tests should not send one.

### Default behaviour

Without any of the headers below, every request is authenticated as the
identifier `test-admin` with the role `ROLE_ADMIN` (plus `ROLE_USER`, which
`KeycloakUser` always adds), the e-mail `admin@test.example.com` and the name
`Test Admin`. This is unchanged from earlier versions.

### Headers

Three request headers adjust the authenticated user per request. Their names
are a public contract and are also available as class constants
(`TestAuthenticator::HEADER_ROLES`, `HEADER_USER`, `HEADER_AUTH`), as are the
two derived identifiers (`ADMIN_IDENTIFIER`, `USER_IDENTIFIER`).

| Header | Effect |
|---|---|
| `X-Test-Roles: ROLE_FOO, ROLE_BAR` | Comma-separated list, whitespace trimmed, empty entries ignored; replaces `ROLE_ADMIN`. The identifier becomes `test-user` (e-mail `user@test.example.com`, name `Test User`) unless the list contains `ROLE_ADMIN`, in which case it stays `test-admin`. An empty value means authenticated with only `ROLE_USER`. |
| `X-Test-User: alice` | Replaces the derived identifier, for example to test ownership checks with two users. Roles, e-mail and name still follow `X-Test-Roles`. An empty or whitespace-only value falls back to the derived identifier. |
| `X-Test-Auth: anonymous` | Case-insensitive. The authenticator steps back (`supports()` returns `false`), no token is created, and `access_control` answers 401 regardless of the other headers. Any other value is ignored. |

### Anonymous requests and the entry point

The bundle ships **no** entry point. Symfony refuses two entry-point
authenticators on one firewall without an explicit `entry_point`, and most
applications already use the Lexik JWT authenticator, which is one. A 401 is
produced in any case: without an entry point Symfony throws an
`HttpException(401)`. The entry point only decides the response body and
headers. There are two documented ways to shape it:

1. The `jwt: ~` authenticator of the base firewall is the entry point. An
   anonymous test request then receives the Lexik JSON body and a
   `WWW-Authenticate: Bearer` header, exactly like a real client without a
   token.
2. The application sets `entry_point: App\Security\MyEntryPoint` on the
   firewall explicitly, and that service answers.

### Example

A functional test with Symfony's test client passes the headers as `HTTP_*`
server parameters:

```php
$client->request('GET', '/api/things', server: [
    'HTTP_X_TEST_ROLES' => 'ROLE_EDITOR',
    'HTTP_X_TEST_USER' => 'alice',
]);
self::assertResponseIsSuccessful();

$client->request('GET', '/api/things', server: [
    'HTTP_X_TEST_AUTH' => 'anonymous',
]);
self::assertResponseStatusCodeSame(401);
```

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

<!-- revised 2026-10-27 (feature list updated, test mode section added) -->
