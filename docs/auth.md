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
- **Cleanup**: `ApiTokenRepository::deleteExpired()`.
