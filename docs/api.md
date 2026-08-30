# API

## Overview

The JSON API is public (no user authentication) under `/api/*`. CORS is configured via `config/packages/nelmio_cors.yaml` using the `CORS_ALLOW_ORIGIN` env var.

Players identify their character using a `token` (hex string generated on character creation). All character-scoped routes use `{token}` as the identifier.

## Routes

### Character

| Method | Route | Description |
|---|---|---|
| `GET` | `/api/characters/{token}` | Get full character sheet. Returns `422` if the character has no kind. |
| `PATCH` | `/api/characters/{token}/stats` | Update current stat values |
| `POST` | `/api/characters/{token}/level-up` | Level up |
| `DELETE` | `/api/characters/{token}` | Delete character |
| `POST` | `/api/characters/{token}/avatar` | Upload avatar image (multipart) |
| `DELETE` | `/api/characters/{token}/avatar` | Remove avatar image |
| `POST` | `/api/characters/{token}/skills` | Attach an encyclopedia skill to the character |
| `DELETE` | `/api/characters/{token}/skills/{skillId}` | Detach a skill from the character |

The character payload (returned by `GET /api/characters/{token}`) includes:

- `kind` — always a non-null object (`{ id, name, bonuses }`). A character **must have a kind** (race); `GET` returns `422 Unprocessable Entity` for a character without one. `characterClass`, by contrast, may be `null`.
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

Damage on weapons, armors and their skills is sent as a `damageLines` array (same shape as the read model — see `docs/damage.md`). Each `DamageLine` is `{ diceCount, diceFaces, fixedAmount, type, element }`. The legacy `damage: { dice, bonus }` shape is **not** supported.

```json
// POST /api/characters/{token}/weapons
{
  "name": "Longsword",
  "description": "",
  "weight": 1500,
  "currentDurabilityPoints": 20,
  "maxDurabilityPoints": 20,
  "isEquipped": false,
  "damageLines": [
    { "diceCount": 2, "diceFaces": 6, "fixedAmount": 3, "type": "physical", "element": null }
  ],
  "skills": [
    {
      "name": "Heavy Strike",
      "exhaustPointCost": 2,
      "actionPointCost": 1,
      "damageLines": [
        { "diceCount": 3, "diceFaces": 6, "fixedAmount": 0, "type": "physical", "element": null }
      ]
    },
    {
      "name": "Steady Stance",
      "exhaustPointCost": 0,
      "actionPointCost": 0
    }
  ]
}
```

Response `201 Created` returns the persisted weapon (resp. armor), including `damageLines` for the item itself and, for each skill, `damageLines` + `isPassive` (same shape as in the character payload — `isPassive` is `true` when the skill has no `damageLines`).

Armors follow the same payload shape **without** the top-level `damageLines` (armors only carry skills, not direct damage).

### Skills (encyclopedia)

| Method | Route | Description |
|---|---|---|
| `GET` | `/api/skills` | List every encyclopedia skill |

Skills are shared across all games and characters (encyclopedia entries — see `docs/encyclopedia.md`). They are created in back-office; the JSON API only exposes them for read and for attachment to a character.

Response `200 OK` is an array of skill objects, each shaped like a `skills[]` entry in the character payload:

```json
[
  {
    "id": 42,
    "name": "Heavy Strike",
    "description": "A targeted blow aimed at the enemy's weak point.",
    "exhaustPointCost": 2,
    "actionPointCost": 1,
    "damageLines": [
      { "diceCount": 2, "diceFaces": 6, "fixedAmount": 3, "type": "physical", "element": null }
    ],
    "isPassive": false
  }
]
```

#### Attach / detach a skill on a character

```json
// POST /api/characters/{token}/skills
{ "skillId": 42 }
```

Response `201 Created` — the attached skill, same shape as above.

Errors:
- `400 Bad Request` — `skillId` missing.
- `404 Not Found` — character token unknown, or skill id unknown.
- `409 Conflict` — skill already attached to this character.

```http
DELETE /api/characters/{token}/skills/{skillId}
```

Response `204 No Content`. Returns `404 Not Found` if the skill is unknown **or** not attached to this character (the two cases are unified — same outcome for the caller).

### Items

| Method | Route | Description |
|---|---|---|
| `GET` | `/api/items` | List every encyclopedia item |
| `POST` | `/api/characters/{token}/items` | Attach an encyclopedia item to the character |
| `PATCH` | `/api/characters/{token}/items/{itemId}` | Update quantity (`quantity` required) |
| `DELETE` | `/api/characters/{token}/items/{itemId}` | Remove item from character |

Items are encyclopedia entries (see `docs/encyclopedia.md`) — `weight`, `description`, `value` are properties of the shared item, not of a single character's instance. Creation of new items happens in the back-office; the JSON API only exposes them for read and for attachment.

#### `GET /api/items`

Response `200 OK` — array of items:

```json
[
  { "id": 7, "name": "Health Potion", "description": "Restores 20 HP.", "weight": 500, "value": 10 }
]
```

`weight` is in grams (cf. `docs/encyclopedia.md` and the front's `displayWeight` helper which converts to kg).

#### `POST /api/characters/{token}/items`

```json
{ "itemId": 7, "quantity": 3 }
```

`quantity` is optional and defaults to `1`. Values below `1` are clamped to `1`.

Response `201 Created` — the attached item, augmented with `quantity` and `currentLoadPoints` (grams):

```json
{
  "id": 7,
  "name": "Health Potion",
  "description": "Restores 20 HP.",
  "weight": 500,
  "value": 10,
  "quantity": 3,
  "currentLoadPoints": 1500
}
```

Errors:
- `400 Bad Request` — `itemId` missing.
- `404 Not Found` — character token unknown, or item id unknown.
- `409 Conflict` — item already attached to this character. Use `PATCH .../items/{itemId}` to change the quantity instead.

#### `PATCH /api/characters/{token}/items/{itemId}` & `DELETE`

Unchanged. Both return `{ currentLoadPoints }` (and `quantity` for PATCH) — `currentLoadPoints` lets the front update the load bar without reloading the character.

### Talents

| Method | Route | Description |
|---|---|---|
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

Each stat increment is added directly to the stat, must be a multiple of that stat's unit, and the increments must total exactly **2 points** (see the unit table in `docs/character.md`). Note `maxLoadPoints` is stored in **grams**: 1 point = `1000` (1 kg), so `maxLoadPoints` must be a multiple of `1000`.
