# Authentication & accounts

## Sign-in identifier: the email

Users sign in with their **email** — on the web back office (`/sign-in`) and, later, on the API.
The `username` is no longer used to sign in.

- **Lookup** (`UserRepository::loadUserByIdentifier()`): exact match first, then a **case-insensitive** match
  (`LOWER(email)`), so accounts created before emails were normalized (mixed case) can still sign in.
  The security provider has no `property`: it delegates to this repository method.
- **Normalization**: `User::setEmail()` trims and lowercases. Existing rows are **not** rewritten — no data migration.
- **`username`** is optional (nullable column, unique index kept). Existing values are preserved; sign-up no longer asks for one.
- **Display**: use `User::getDisplayName()` (username if set, email otherwise) — never `username` directly, it may be `null`.
- The web form keeps the `_username` POST parameter name (Symfony `form_login` default); it carries the email.

## API access tokens

The API authenticates users with **opaque access tokens** (Symfony `access_token` authenticator — no JWT).

- **Storage** (`App\Entity\ApiToken`, table `api_token`): only the **SHA-256 hash** of the token is stored
  (`token_hash`, unique), with `expires_at`, `created_at`, `last_used_at`. Tokens are deleted with their user (`ON DELETE CASCADE`).
- **Issuing** (`App\Security\ApiTokenManager::issue()`): generates `rpg_` + 64 random hex chars, persists the hash,
  and returns the plain token — the **only** time it is available. Lifetime: **30 days** (`ApiTokenManager::LIFETIME`), no refresh token: the user signs in again.
- **Lookup**: `ApiTokenRepository::findOneByPlainToken()` hashes then queries; expiry is checked by the caller (`ApiToken::isExpired()`).
- **Cleanup**: `ApiTokenRepository::deleteExpired()`, run by `bin/console app:api-token:purge` (to schedule in a cron, e.g. daily).
- **Authentication** (`App\Security\ApiTokenHandler`, firewall `api` in `security.yaml`, pattern `^/api/`, stateless):
  reads `Authorization: Bearer <token>`, rejects unknown/expired tokens (`401` + `WWW-Authenticate`), refreshes
  `lastUsedAt` at most every 5 minutes (no write on every request).
  - No header → anonymous: the `access_control` for `^/api/` stays `PUBLIC_ACCESS`, so the public character-token
    routes are unchanged. Protected routes declare `#[IsGranted('IS_AUTHENTICATED')]`.
  - A sent token is always checked, even on public routes (no silent fallback to anonymous).
- **Current user**: `GET /api/me`.

## Sign-in & sign-out (API)

- `POST /api/auth/login` with a JSON `{ email, password }`: handled by the `json_login` authenticator of the `api` firewall
  (the controller action is only reached for a non-JSON request → `400`). Same email lookup as the web (case-insensitive).
  - Success (`App\Security\ApiLoginSuccessHandler`): issues a token → `{ token, expiresAt, user }`. Each sign-in issues a
    new token (one per device); no session is created.
  - Failure (`App\Security\ApiLoginFailureHandler`): `401` `{ "error": "Invalid credentials." }`, the same for an unknown
    email and a wrong password; `429` when throttled.
- `POST /api/auth/logout`: deletes the token used for the request (`204`). Other tokens of the user stay valid.

## Characters & accounts

`Character.user` (nullable) is the owner of a sheet. Existing sheets without owner keep working through their token.

- `GET /api/me/characters`: the user's characters (light summary with the `token`; the sheet is still played through the token routes).
- `POST /api/me/characters/claim` `{ token }`: links an unowned sheet to the user — whoever holds the token can claim it,
  like today's full access through the token. Idempotent for the owner, `409` if another account owns it. No data is
  rewritten other than `user_id`.
- A signed-in import (`POST /api/characters/import` with a bearer token) belongs to the user; an anonymous one has no owner.
- The token routes stay public and unchanged for now (owned sheets included). Restricting them to the owner/GM comes
  with the voters, then their removal once sheets are migrated.

## Rate limiting

`symfony/rate-limiter`, state stored in the `cache.rate_limiter` pool (filesystem cache by default).

- **Sign-in** (`login_throttling` on both the `api` and `main` firewalls): 5 failed attempts per minute per email + IP,
  25 per minute per IP. A successful sign-in resets the counter.
- **Registration** (limiter `registration` in `config/packages/rate_limiter.yaml`, API and web sign-up): 10 attempts per
  hour per IP, failed ones included (it also slows down probing which emails have an account). API: `429` + `Retry-After`;
  web: the form is re-rendered with an error (`429`).
- Tests: `App\Tests\Support\ResetsRateLimiters` clears the pool before each test (an in-memory pool would not work:
  the kernel resets it between requests, even with `disableReboot()`).
- Behind a reverse proxy, limits are keyed by `Request::getClientIp()`: configure `trusted_proxies`, otherwise every
  client shares the proxy IP.

## Registration (API and web)

Both the API (`POST /api/auth/register`, `{ email, password }`) and the web sign-up form (`/sign-up`, `App\FormType\UserType`)
share the same rules: data in `App\Dto\RegisterUserRequest` (Symfony Validator constraints), account built by
`App\Factory\UserFactory::createOneFromRegistration()` (hashes the password; the controller persists it).

- `email`: required, valid email, max 180 chars; normalized (trimmed, lowercased). Must not be used by another account —
  checked **case-insensitively** by `App\Validator\UniqueUserEmail` (legacy mixed-case accounts), plus the unique index as a safety net for concurrent requests.
- `password`: required, 8 to 4096 chars; hashed with the configured password hasher.
- No `username` is asked (display falls back to the email).
- API: returns `201` with the user; it does **not** sign the user in — the client then calls the login route.
  Errors: `422` `{ error, violations: { field: [messages] } }` (an already used email is reported on `email`), `400` for an invalid JSON body.
- Web: redirects to `/sign-in` on success; an invalid submission re-renders the form with its errors (`422`).
