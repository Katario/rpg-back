# Games

A game is a table: **one game master** (its creator, `Game.gameMaster`) and **players**, who join it with an
invite code. Every character belongs to exactly one game.

> Rollout: the back office (Twig) is the game master's tool; the React front is for players only.
> The legacy access (anonymous import, character token routes) keeps working until production is migrated.

## Model

- **`Game`**: `name`, `note`, `gameMaster` (a `User`), `inviteCode`, `archivedAt`, plus the beings and equipment of the game.
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

## Migration of existing data

`Version20261115210404` is additive (new table, two nullable columns). Players were implicit before (owners of a
character of the game): every character owner becomes a member of the game of their character, except the game master.
Existing games start closed (no invite code).
