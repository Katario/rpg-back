# API

## Overview

The JSON API is public (no user authentication) under `/api/*`. CORS is configured via `config/packages/nelmio_cors.yaml` using the `CORS_ALLOW_ORIGIN` env var.

Players identify their character using a `token` (hex string generated on import). All character-scoped routes use `{token}` as the identifier.

## Routes

### Character

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/characters/import` | Import a character from JSON |
| `GET` | `/api/characters/{token}` | Get full character sheet |
| `PATCH` | `/api/characters/{token}/stats` | Update current stat values |
| `POST` | `/api/characters/{token}/level-up` | Level up |
| `DELETE` | `/api/characters/{token}` | Delete character |
| `POST` | `/api/characters/{token}/avatar` | Upload avatar image |

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

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/characters/{token}/items` | Add item to character |
| `PATCH` | `/api/characters/{token}/items/{itemId}` | Update quantity (`quantity` required) |
| `DELETE` | `/api/characters/{token}/items/{itemId}` | Remove item |

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

## Level-up payload

```json
// POST /api/characters/{token}/level-up
{
  "stats": { "maxHealthPoints": 1, "maxManaPoints": 1 },
  "talents": ["Alchimie", "Précision", "Discrétion", "Mysticisme", "Athlétisme"]
}
```
