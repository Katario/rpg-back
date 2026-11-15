# Games

A game is a table: **one game master** (its creator, `Game.gameMaster`) and **players**, who join it with an
invite code. Every character belongs to exactly one game.

> Rollout: the back office (Twig) is the game master's tool; the React front is for players only.
> The legacy access (anonymous import, character token routes) keeps working until production is migrated.

## Model

- **`Game`**: `name`, `note`, `gameMaster` (a `User`, required), `inviteCode`, `archivedAt`.
- **Belonging to a game** (required, `NOT NULL`): every `Being` (characters, monsters, NPCs: `being.game_id`) and every
  `Equipment` (`equipment.game_id`). `Game` has no inverse collection for beings — `Being.game` is declared on the
  abstract STI root, which Doctrine cannot map to one collection per subclass: query `CharacterRepository`,
  `MonsterRepository`… (`findByGameBySearch()`). `Game::getEquipments()` remains.
- **`GameMembership`** (table `game_membership`): a player of a game — `game`, `user`, `joinedAt`, unique per
  (game, user). The game master is **never** a member of their own game (`Game::addMember()` refuses it).
  - `Game::addMember()` is idempotent; `Game::removeMember()` deletes the membership only (orphan removal):
    the player's characters are left untouched.
  - Users are compared by object, or by id (two instances of the same row).
- **Invite code** (`game.invite_code`, nullable, unique): `null` = the game is **closed**, nobody can join. The game
  master creates, replaces or deletes it at will (e.g. delete it once every player joined, recreate one later for a
  newcomer). Stored normalized: 8 uppercase characters, no dash.
- **Archiving** (`game.archived_at`, nullable): replaces deletion — nothing is deleted. `Game::archive()` also deletes
  the invite code; `Game::unarchive()` does not reopen the game. An archived game is hidden and closed to new
  players, its characters keep working.

## Players & invite codes

`App\Game\GameMembershipManager` (every method flushes):

- `generateInviteCode(Game)`: creates or **replaces** the code (the previous one stops working); refused for an archived
  game. `deleteInviteCode(Game)`: closes the game.
- `join(User, code)`: the code as typed by the player (`App\Game\InviteCode::normalize()`: case, spaces and dashes do not
  matter). Idempotent. `InviteCodeNotFoundException` for an unknown or malformed code, or an archived game (no
  distinction, so codes cannot be probed); `GameMasterCannotJoinException` for the game master.
- `leave(Game, User)` (the player) / `remove(Game, User)` (the game master): deletes the membership and **disables** the
  player's characters of that game — they keep them. `NotAMemberException` if the user is not a player.
  Rejoining does not enable them again: the game master does.

Invite codes (`App\Game\InviteCode`): 8 characters from `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (no look-alikes `0/O`,
`1/I`), stored as `K7QM3XPA`, displayed as `K7QM-3XPA` (`InviteCode::format()`).

## Back office (game master)

On the game page (`/games/{id}`, `GameVoter::MANAGE`):

- **Invitation** block: the code (`invite_code` Twig filter → `K7QM-3XPA`), or "closed"; create / replace / delete it
  (`App\Controller\GamePlayerController`). There is no link: players type the code in the player app.
- **Players** block: the members, with their join date; remove one (confirmation; their characters are disabled).
- **Characters** block: each card shows the player and a disable / enable button (`toggle_character`,
  `CharacterVoter::EDIT`).

Every action is a `POST` with a CSRF token (`#[IsCsrfTokenValid]`, checked before the permission), then redirects to
the game page.

## Permissions

`App\Security\Voter\GameVoter`:

- `MANAGE` — the game master, or an admin; the subject is a `Game` or anything belonging to one (back office).
- `PLAY` — the players and the game master, while the game is **not archived** (e.g. importing a character into it);
  the subject is a `Game`. Admins get no implicit `PLAY`.

## Disabled characters

`Character.isDisabled` (`being.is_disabled`, default `false`): a disabled sheet is **read-only on the API**. It stays
readable (sheet, export, `GET /api/me/characters` with `disabled: true`), but every change to
`/api/characters/{token}/...` (`POST`, `PATCH`, `DELETE`) gets a `403` `{ "error": "This character is disabled." }`,
whatever the access (character token or account).

- Enforced in one place, `App\EventListener\DisabledCharacterGuard` (kernel request listener): every `api_character_*`
  route with a `{token}` and an unsafe method — new write routes are covered without extra code.
- A character is disabled when its owner leaves or is removed from the game (they keep it); the game master can disable
  or enable it from the back office, where they can still edit it. Archiving a game does not disable its characters.

## Migration of existing data

`Version20261115210404` is additive (new table, two nullable columns). Players were implicit before (owners of a
character of the game): every character owner becomes a member of the game of their character, except the game master.
Existing games start closed (no invite code).

`Version20261115212047` sets `game.game_master`, `being.game_id` and `equipment.game_id` to `NOT NULL`. It rewrites no
data: it stops, with nothing applied, if a row still has no game / game master (production inventory of 2026-11-15: none).

`Version20261115214031` adds `being.is_disabled` (default `false`, nothing rewritten).
