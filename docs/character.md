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

## Token

Generated on character creation with `bin2hex(random_bytes(16))`. Immutable after creation. Used as the identifier for all player-facing API routes (`/api/characters/{token}/...`).
