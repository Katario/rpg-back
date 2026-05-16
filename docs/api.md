# API

## Overview

The JSON API is public (no user authentication) under `/api/*`. CORS is configured via `config/packages/nelmio_cors.yaml` using the `CORS_ALLOW_ORIGIN` env var.

Players identify their character using a `token` (hex string generated on import). All character-scoped routes use `{token}` as the identifier.

## Routes

### Character

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/characters/import` | Import a character from JSON. Returns the full character payload (same shape as `GET /api/characters/{token}`) with status `201`. |
| `GET` | `/api/characters/{token}` | Get full character sheet |
| `PATCH` | `/api/characters/{token}/stats` | Update current stat values |
| `POST` | `/api/characters/{token}/level-up` | Level up |
| `DELETE` | `/api/characters/{token}` | Delete character |
| `POST` | `/api/characters/{token}/avatar` | Upload avatar image (multipart) |
| `DELETE` | `/api/characters/{token}/avatar` | Remove avatar image |

The character payload (returned by `GET /api/characters/{token}` and `POST /api/characters/import`) includes:

- `weapons[].damageLines`, `armors[].damageLines`, `skills[].damageLines`, `spells[].damageLines` — raw array of damage line objects: `{ diceCount, diceFaces, fixedAmount, type, element }` (see `docs/damage.md`).
- `spells[].type` — `"active"` or `"passive"`.
- `skills[].isPassive`, `spells[].isPassive` — `true` when the entry has no `damageLines`. Currently derived on the fly; will be backed by a persisted column in an upcoming migration.

### Weapons & Armors

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/characters/{token}/weapons` | Create weapon |
| `PATCH` | `/api/characters/{token}/weapons/{id}` | Update weapon |
| `DELETE` | `/api/characters/{token}/weapons/{id}` | Delete weapon |
| `POST` | `/api/characters/{token}/weapons/{id}/skills` | Add skill |
| `DELETE` | `/api/characters/{token}/weapons/{id}/skills/{skillId}` | Remove skill |
| `POST` | `/api/characters/{token}/armors` | Create armor |
| `PATCH` | `/api/characters/{token}/armors/{id}` | Update armor |
| `DELETE` | `/api/characters/{token}/armors/{id}` | Delete armor |
| `POST` | `/api/characters/{token}/armors/{id}/skills` | Add skill |
| `DELETE` | `/api/characters/{token}/armors/{id}/skills/{skillId}` | Remove skill |

### Items

| Method | Route | Description | Response |
|---|---|---|---|
| `POST` | `/api/characters/{token}/items` | Add item to character | `{ id, quantity, currentLoadPoints }` |
| `PATCH` | `/api/characters/{token}/items/{itemId}` | Update quantity (`quantity` required) | `{ currentLoadPoints }` |
| `DELETE` | `/api/characters/{token}/items/{itemId}` | Remove item | `{ currentLoadPoints }` |

`currentLoadPoints` is returned in grams after every inventory mutation so the front can update the load bar without a full character reload.

### Talents

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/talents/import` | Bulk import talents with TalentLevels |
| `GET` | `/api/characters/{token}/talents/{talentId}/levels` | Get unlocked TalentLevels for a talent |

### Misc

| Method | Route | Description |
|---|---|---|
| `GET` | `/api/health` | Health check |

## Response conventions

- `201 Created` — resource created, returns the serialized object (not just the id)
- `204 No Content` — update/delete successful, no body
- `400 Bad Request` — missing or invalid fields
- `404 Not Found` — resource not found
- `409 Conflict` — duplicate (e.g. character with same name in same game)
- `422 Unprocessable Entity` — business rule violation (e.g. invalid level-up)

## PATCH stats

```json
// PATCH /api/characters/{token}/stats
// All fields optional — only provided fields are updated
{
  "currentHealthPoints": 10,
  "currentManaPoints": 5,
  "currentActionPoints": 120,
  "currentExhaustPoints": 140,
  "currentMentalPoints": 80
}
```

Validation:

- Each provided field must be a JSON integer. Strings, floats, booleans, etc. are rejected with `400 Bad Request`.
- Each provided value must satisfy `0 <= value <= max*Points` (using the character's current max for the corresponding stat). Out-of-range values return `422 Unprocessable Entity`.
- Unknown fields are silently ignored. Returns `204 No Content` on success.

## Avatar upload

`POST /api/characters/{token}/avatar` expects `multipart/form-data` with a single field `avatar`.

- **Allowed MIME types**: `image/jpeg`, `image/png`, `image/webp`
- **Max size**: 5 MB
- **Validation**: the file must be a decodable image (not just a matching MIME type)
- On success, the previous avatar file is deleted from disk.

```http
POST /api/characters/abc123/avatar
Content-Type: multipart/form-data; boundary=...

avatar=@portrait.png
```

Response `200 OK`:
```json
{ "avatarUrl": "https://api.example.com/uploads/avatars/<random>.png" }
```

Errors:
- `400 Bad Request` — no file, invalid MIME, file too large, not a real image
- `404 Not Found` — character token unknown

`DELETE /api/characters/{token}/avatar` removes the avatar file from disk and resets `avatarUrl` to `null`. Returns `204 No Content` (idempotent: returns 204 even if no avatar was set).

## Level-up payload

```json
// POST /api/characters/{token}/level-up
{
  "stats": { "maxHealthPoints": 1, "maxManaPoints": 1 },
  "talents": ["Alchimie", "Précision", "Discrétion", "Mysticisme", "Athlétisme"]
}
```
