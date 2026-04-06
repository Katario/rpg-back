# Equipment

## Overview

`Equipment` is an abstract entity using Single Table Inheritance on the `equipment` table. Two concrete types:
- `Weapon` — has `damageLines` and `Skills` (attacks)
- `Armor` — has `damageLines` and `Skills`

Both belong to a `Game` and optionally to a `Being`.

## Weight Convention

All weights are stored in **grams** (int). Never store floats.

| Example | Stored value |
|---|---|
| 1 kg sword | `1000` |
| 20 kg armor | `20000` |
| 0.1 kg ration | `100` |

The front is responsible for display conversion. The JSON import multiplies source values by 1000.

## Skills as Attacks

Weapon attacks are modelled as `Skill` entities linked to the weapon via the `equipments_skills` join table. Each skill has:
- `name` — the attack name (e.g. "Tir", "TAPER")
- `exhaustPointCost` — FA cost (`faCost` in import JSON)
- `actionPointCost` — PA cost (`paCost` in import JSON)
- `damageLines` — parsed from the attack's `damage` string on import

Skills are **encyclopedia entries** (shared globally). On import, an existing skill with the same name is reused and linked to the new weapon — not duplicated.

## Damage Lines

Both `Weapon` and `Armor` have a `damageLines` JSON column. See `docs/damage.md` for the full `DamageLine` reference.

```php
$weapon->getDamageLines(): DamageLine[]
$weapon->setDamageLines(DamageLine[] $lines): static
$weapon->addDamageLine(DamageLine $line): static
$weapon->removeDamageLine(int $faces): static
```

## API Routes

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/characters/{token}/weapons` | Create weapon (returns full object) |
| `PATCH` | `/api/characters/{token}/weapons/{id}` | Update weapon fields |
| `DELETE` | `/api/characters/{token}/weapons/{id}` | Delete weapon |
| `POST` | `/api/characters/{token}/weapons/{id}/skills` | Add skill to weapon |
| `DELETE` | `/api/characters/{token}/weapons/{id}/skills/{skillId}` | Remove skill from weapon |
| `POST` | `/api/characters/{token}/armors` | Create armor (returns full object) |
| `PATCH` | `/api/characters/{token}/armors/{id}` | Update armor fields |
| `DELETE` | `/api/characters/{token}/armors/{id}` | Delete armor |
| `POST` | `/api/characters/{token}/armors/{id}/skills` | Add skill to armor |
| `DELETE` | `/api/characters/{token}/armors/{id}/skills/{skillId}` | Remove skill from armor |

## Creation Payload

### Weapon
```json
{
  "name": "Arc en bois",
  "description": "",
  "weight": 20000,
  "currentDurabilityPoints": 10,
  "maxDurabilityPoints": 10,
  "isEquipped": true,
  "damageLines": [
    { "diceCount": 1, "diceFaces": 6, "fixedAmount": 0, "type": "physical", "element": null }
  ],
  "skills": [
    {
      "name": "Tir",
      "exhaustPointCost": 5,
      "actionPointCost": 110,
      "damageLines": [
        { "diceCount": 1, "diceFaces": 6, "fixedAmount": 0, "type": "physical", "element": null }
      ]
    }
  ]
}
```

### Armor
Same structure without top-level `damageLines` being mandatory. `skills` is optional in both cases.
