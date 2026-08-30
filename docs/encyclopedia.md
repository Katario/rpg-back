# Encyclopedia

## Status

**Not yet fully implemented.** The encyclopedia pattern exists in the codebase but the backoffice management UI for it is incomplete.

## Concept

`Encyclopedia` (`src/Entity/Encyclopedia.php`) is a `MappedSuperclass` for shared game-reference content. Entities extending it are **global** — shared across all games and characters:

- `Skill`
- `Talent`
- `Spell`
- `Item`
- `EquipmentTemplate`
- `MonsterTemplate`
- `CharacterTemplate`
- `NonPlayableCharacterTemplate`

## Implication

When an entity references an encyclopedia entry (e.g. attaching a `Skill` to a weapon), the entry is **looked up by name** and reused if it exists. Entries are never duplicated. A `Skill` named "Tir" reused for the next weapon with the same attack.

This means editing an encyclopedia entry (e.g. changing a Skill's damage) affects **all characters** that reference it.
