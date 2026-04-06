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

## Implication for imports

When importing a character, encyclopedia entries are **looked up by name** and reused if they exist. They are never duplicated. A `Skill` named "Tir" created during one character's import will be reused for the next character with the same attack.

This means editing an encyclopedia entry (e.g. changing a Skill's damage) affects **all characters** that reference it.
