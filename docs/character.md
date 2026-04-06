# Character

## Overview

`Character` extends `Being` (Single Table Inheritance). Players access their sheet via a `token` (hex string) — no user authentication required on the API.

## Stats

| JSON key | Entity field | Description |
|---|---|---|
| `pv` | `healthPoints` | Hit points |
| `ma` | `manaPoints` | Mana points |
| `pa` | `actionPoints` | Action points |
| `fa` | `exhaustPoints` | Exhaust/fatigue points |
| `ch` | `loadPoints` | Carry load (stored in **grams**) |
| `sm` | `mentalPoints` | Mental/sanity points |

`currentLoadPoints` is **computed** dynamically by `LoadCalculator` (sum of equipment weights + items × quantity). It is never stored.

## Level-Up Rules

On level-up, the player distributes **2 points** across stats. Each stat has a unit value:

| Stat | Points → Value |
|---|---|
| `maxHealthPoints` | 1 pt = +1 |
| `maxManaPoints` | 1 pt = +1 |
| `maxActionPoints` | 1 pt = +10 |
| `maxExhaustPoints` | 1 pt = +10 |
| `maxMentalPoints` | 1 pt = +10 |
| `maxLoadPoints` | 1 pt = +10 |

Increments must be exact multiples of the unit value. The sum of (increment ÷ unit) must equal exactly 2. Additionally, exactly 5 talents must be provided (each gets +3 if primary, +2 if secondary, +1 otherwise).

Validation is in `Character::levelUp()` (`src/Entity/Character.php`).

## Import

**Route:** `POST /api/characters/import`

- `gameId` is optional — defaults to `1`
- Duplicate detection: same name + game + kind → `409 Conflict`
- Weights from JSON are multiplied by 1000 (converted to grams)
- Attacks on weapons are imported as `Skill` entities linked to the weapon
- Duplicate items in the JSON (same name) increment quantity instead of creating a new `BeingItem`
- `Skill`, `Talent`, `Spell` are looked up by name and reused if they exist (encyclopedia pattern)

## Token

Generated on import with `bin2hex(random_bytes(16))`. Immutable after creation. Used as the identifier for all player-facing API routes (`/api/characters/{token}/...`).
