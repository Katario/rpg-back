# Damage System

## Overview

Damage is modelled as a collection of `DamageLine` objects. Each line represents one damage source (e.g. "2d6 physical fire"). An entity can have zero, one, or several lines.

## DamageLine ValueObject

**File:** `src/ValueObject/DamageLine.php`

| Field | Type | Description |
|---|---|---|
| `diceCount` | int | Number of dice (e.g. 2 for "2d6") |
| `diceFaces` | int | Faces per die (e.g. 6 for "2d6") |
| `fixedAmount` | int | Flat bonus added to the roll |
| `type` | DamageTypeEnum | `physical` or `magical` — defaults to `physical` |
| `element` | ?ElementEnum | `fire`, `ice`, `thunder`, or `null` |

### Key methods

```php
DamageLine::fromArray(array $data): self   // deserialize from JSON array
$line->toArray(): array                    // serialize to JSON array
```

## Enums

**`src/Enum/DamageTypeEnum.php`**
- `PHYSICAL = 'physical'`
- `MAGICAL = 'magical'`

**`src/Enum/ElementEnum.php`**
- `FIRE = 'fire'`
- `ICE = 'ice'`
- `THUNDER = 'thunder'`
- nullable — most physical damage has no element

## Storage

Each entity stores damage as a JSON column named `damageLines`:

| Entity | Column |
|---|---|
| `Weapon` | `damageLines` |
| `Armor` | `damageLines` |
| `Skill` | `damageLines` |
| `Spell` | `damageLines` |

### Entity methods

```php
$entity->getDamageLines(): DamageLine[]
$entity->setDamageLines(DamageLine[] $lines): static
$entity->addDamageLine(DamageLine $line): static
$entity->removeDamageLine(int $faces): static  // removes all lines with matching diceFaces
```

## API format

```json
"damageLines": [
  {
    "diceCount": 2,
    "diceFaces": 6,
    "fixedAmount": 0,
    "type": "physical",
    "element": null
  },
  {
    "diceCount": 1,
    "diceFaces": 4,
    "fixedAmount": 2,
    "type": "magical",
    "element": "fire"
  }
]
```

## Import from JSON character sheet

The `parseDamageString()` method in `CharacterController` parses legacy damage strings (e.g. `"2d6"`, `"1d4 +2"`) into a single `DamageLine`. Type defaults to `physical`, element to `null`. Complex strings like `"1d4 +2 au voyage"` are parsed for dice only — text bonuses are ignored.
