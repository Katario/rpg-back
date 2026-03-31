<?php

declare(strict_types=1);

namespace App\Fixtures\DataFixtures;

use App\Fixtures\DataFixtures\Factory\ArmamentFactory;
use App\Fixtures\DataFixtures\Factory\ArmamentTemplateFactory;
use App\Fixtures\DataFixtures\Factory\BeingTalentFactory;
use App\Fixtures\DataFixtures\Factory\CharacterClassFactory;
use App\Fixtures\DataFixtures\Factory\KindTalentBonusFactory;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\CharacterTemplateFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\ItemFactory;
use App\Fixtures\DataFixtures\Factory\KindFactory;
use App\Fixtures\DataFixtures\Factory\MonsterFactory;
use App\Fixtures\DataFixtures\Factory\MonsterTemplateFactory;
use App\Fixtures\DataFixtures\Factory\NonPlayableCharacterFactory;
use App\Fixtures\DataFixtures\Factory\NonPlayableCharacterTemplateFactory;
use App\Fixtures\DataFixtures\Factory\SkillFactory;
use App\Fixtures\DataFixtures\Factory\SpecieFactory;
use App\Fixtures\DataFixtures\Factory\SpellFactory;
use App\Fixtures\DataFixtures\Factory\TalentFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class InitialFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['initial'];
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Create Users
        $mainAccount = UserFactory::createOne([
            'email' => 'katario@fixture.com',
            'username' => 'katario',
            'password' => 'test',
            'roles' => [],
        ]);

        $secondAccount = UserFactory::createOne([
            'email' => 'red@fixture.com',
            'username' => 'red',
            'password' => 'test',
            'roles' => [],
        ]);

        $thirdAccount = UserFactory::createOne([
            'email' => 'blue@fixture.com',
            'username' => 'blue',
            'password' => 'test',
            'roles' => [],
        ]);

        $fourthAccount = UserFactory::createOne([
            'email' => 'green@fixture.com',
            'username' => 'green',
            'password' => 'test',
            'roles' => [],
        ]);

        $fifthAccount = UserFactory::createOne([
            'email' => 'yellow@fixture.com',
            'username' => 'yellow',
            'password' => 'test',
            'roles' => [],
        ]);

        UserFactory::createMany(3);

        // 2. Create Games
        $mainGame = GameFactory::createOne([
            'gameMaster' => $mainAccount,
            'name' => 'First Game',
        ]);

        $secondGame = GameFactory::createOne([
            'gameMaster' => $secondAccount,
            'name' => 'Second Game',
        ]);

        // 3. Fill Encyclopedia
        // 3.1. Create Kind, CharacterClass, Items, Skills && Spells
        $human = KindFactory::createOne([
            'name' => 'Human',
        ]);
        $elf = KindFactory::createOne([
            'name' => 'Elf',
        ]);
        $dwarf = KindFactory::createOne([
            'name' => 'Dwarf',
        ]);
        $orc = KindFactory::createOne([
            'name' => 'Orc',
        ]);
        $warrior = CharacterClassFactory::createOne([
            'name' => 'Warrior',
        ]);
        $magician = CharacterClassFactory::createOne([
            'name' => 'Magician',
        ]);
        $priest = CharacterClassFactory::createOne([
            'name' => 'Priest',
        ]);
        $hunter = CharacterClassFactory::createOne([
            'name' => 'Hunter',
        ]);
        ItemFactory::createOne([
            'name' => 'Healing Potion',
        ]);
        ItemFactory::createOne([
            'name' => 'Poison Potion',
        ]);
        ItemFactory::createOne([
            'name' => 'Burning Potion',
        ]);
        ItemFactory::createOne([
            'name' => 'Blue Key',
        ]);
        ItemFactory::createOne([
            'name' => 'Herbs',
        ]);

        $goblin = SpecieFactory::createOne([
            'name' => 'Goblin',
        ]);
        $wolf = SpecieFactory::createOne([
            'name' => 'Wolf',
        ]);
        $orcSpecie = SpecieFactory::createOne([
            'name' => 'Orc',
        ]);
        $griffin = SpecieFactory::createOne([
            'name' => 'Griffin',
        ]);
        $slime = SpecieFactory::createOne([
            'name' => 'Slime',
        ]);
        $rat = SpecieFactory::createOne([
            'name' => 'Rat',
        ]);

        SkillFactory::createOne([
            'name' => 'Punch',
        ]);
        SkillFactory::createOne([
            'name' => 'Kick',
        ]);
        SkillFactory::createOne([
            'name' => 'Fire Punch',
        ]);
        SkillFactory::createOne([
            'name' => 'Ice Punch',
        ]);
        SkillFactory::createOne([
            'name' => 'Thunder Punch',
        ]);

        TalentFactory::createOne(['name' => 'Acrobatie']);
        $talentAlchimie = TalentFactory::createOne(['name' => 'Alchimie']);
        TalentFactory::createOne(['name' => 'Altération']);
        TalentFactory::createOne(['name' => 'Arme Contondante']);
        $talentArmureLegere = TalentFactory::createOne(['name' => 'Armure légère']);
        TalentFactory::createOne(['name' => 'Armure lourde']);
        TalentFactory::createOne(['name' => 'Armurerie']);
        $talentAthletisme = TalentFactory::createOne(['name' => 'Athlétisme']);
        TalentFactory::createOne(['name' => 'Combat sans armure']);
        TalentFactory::createOne(['name' => 'Destruction']);
        TalentFactory::createOne(['name' => 'Discrétion']);
        TalentFactory::createOne(['name' => 'Eloquence']);
        TalentFactory::createOne(['name' => 'Enchantement']);
        TalentFactory::createOne(['name' => 'Esquive']);
        TalentFactory::createOne(['name' => 'Guérison']);
        TalentFactory::createOne(['name' => 'Hache']);
        TalentFactory::createOne(['name' => 'Illusion']);
        TalentFactory::createOne(['name' => 'Invocation']);
        TalentFactory::createOne(['name' => 'Lame Courte']);
        TalentFactory::createOne(['name' => 'Lame Longue']);
        TalentFactory::createOne(['name' => 'Lance']);
        TalentFactory::createOne(['name' => 'Marchandage']);
        $talentMysticisme = TalentFactory::createOne(['name' => 'Mysticisme']);
        TalentFactory::createOne(['name' => 'Parade']);
        $talentPrecision = TalentFactory::createOne(['name' => 'Précision']);
        TalentFactory::createOne(['name' => 'Pugilat']);
        TalentFactory::createOne(['name' => 'Sécurité']);

        // Kind bonuses
        KindTalentBonusFactory::createOne(['kind' => $human, 'talent' => $talentAthletisme, 'value' => 2]);
        KindTalentBonusFactory::createOne(['kind' => $elf, 'talent' => $talentPrecision, 'value' => 5]);
        KindTalentBonusFactory::createOne(['kind' => $elf, 'talent' => $talentMysticisme, 'value' => 3]);
        KindTalentBonusFactory::createOne(['kind' => $dwarf, 'talent' => $talentAlchimie, 'value' => 5]);
        KindTalentBonusFactory::createOne(['kind' => $dwarf, 'talent' => $talentArmureLegere, 'value' => 3]);
        KindTalentBonusFactory::createOne(['kind' => $orc, 'talent' => $talentArmureLegere, 'value' => 4]);
        KindTalentBonusFactory::createOne(['kind' => $orc, 'talent' => $talentAthletisme, 'value' => 2]);

        SpellFactory::createOne([
            'name' => 'Fire ball',
        ]);
        SpellFactory::createOne([
            'name' => 'Water ball',
        ]);
        SpellFactory::createOne([
            'name' => 'Thunder ball',
        ]);
        SpellFactory::createOne([
            'name' => 'Ice ball',
        ]);
        SpellFactory::createOne([
            'name' => 'Seed ball',
        ]);

        $steelSword = ArmamentTemplateFactory::createOne([
            'name' => 'Steel Sword',
            'spells' => SpellFactory::randomRange(0, 1),
            'skills' => SkillFactory::randomRange(0, 1),
        ]);

        $steelHelmet = ArmamentTemplateFactory::createOne([
            'name' => 'Steel Helmet',
            'spells' => SpellFactory::randomRange(0, 1),
            'skills' => SkillFactory::randomRange(0, 1),
        ]);

        $steelArmor = ArmamentTemplateFactory::createOne([
            'name' => 'Steel Armor',
            'spells' => SpellFactory::randomRange(0, 1),
            'skills' => SkillFactory::randomRange(0, 1),
        ]);

        CharacterTemplateFactory::createOne([
            'name' => 'Template of a Warrior',
            'kind' => $human,
            'characterClass' => $warrior,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);

        CharacterTemplateFactory::createOne([
            'name' => 'Template of a Priest',
            'kind' => $human,
            'characterClass' => $priest,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);

        CharacterTemplateFactory::createOne([
            'name' => 'Template of a Hunter',
            'kind' => $elf,
            'characterClass' => $hunter,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);

        NonPlayableCharacterTemplateFactory::createOne([
            'name' => 'Template of a Warrior',
            'kind' => $human,
            'characterClass' => $warrior,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);

        NonPlayableCharacterTemplateFactory::createOne([
            'name' => 'Template of a Priest',
            'kind' => $human,
            'characterClass' => $priest,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);

        NonPlayableCharacterTemplateFactory::createOne([
            'name' => 'Template of a Hunter',
            'kind' => $elf,
            'characterClass' => $hunter,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);

        MonsterTemplateFactory::createOne([
            'name' => 'Goblin template',
            'specie' => $goblin,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);
        MonsterTemplateFactory::createOne([
            'name' => 'Wolf template',
            'specie' => $wolf,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);
        MonsterTemplateFactory::createOne([
            'name' => 'Griffin template',
            'specie' => $griffin,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);
        MonsterTemplateFactory::createOne([
            'name' => 'Slime template',
            'specie' => $slime,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);
        MonsterTemplateFactory::createOne([
            'name' => 'Rat template',
            'specie' => $rat,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);

        $characterRed = CharacterFactory::createOne([
            'user' => $secondAccount,
            'game' => $mainGame,
            'name' => 'Red',
            'kind' => $human,
            'characterClass' => $warrior,
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);
        $characterBlue = CharacterFactory::createOne([
            'user' => $thirdAccount,
            'game' => $mainGame,
            'name' => 'Blue',
            'kind' => $elf,
            'characterClass' => $priest,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);
        $characterGreen = CharacterFactory::createOne([
            'user' => $fourthAccount,
            'game' => $mainGame,
            'name' => 'Green',
            'kind' => $human,
            'characterClass' => $hunter,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);

        NonPlayableCharacterFactory::createOne([
            'game' => $mainGame,
            'name' => 'Maurice',
            'kind' => $human,
            'characterClass' => $hunter,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);
        NonPlayableCharacterFactory::createOne([
            'game' => $mainGame,
            'name' => 'Michel',
            'kind' => $human,
            'characterClass' => $hunter,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);
        NonPlayableCharacterFactory::createOne([
            'game' => $mainGame,
            'name' => 'Jacques',
            'kind' => $human,
            'characterClass' => $hunter,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);

        MonsterFactory::createOne([
            'game' => $mainGame,
            'name' => 'Green Goblin',
            'specie' => $goblin,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);
        MonsterFactory::createOne([
            'game' => $mainGame,
            'name' => 'Archer Goblin',
            'specie' => $goblin,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);
        MonsterFactory::createOne([
            'game' => $mainGame,
            'name' => 'HobGoblin',
            'specie' => $goblin,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);
        MonsterFactory::createOne([
            'game' => $mainGame,
            'name' => 'Blue Slime',
            'specie' => $slime,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
            'items' => ItemFactory::randomRange(0, 3),
        ]);

        BeingTalentFactory::createMany(
            15,
            function () {
                return [
                    'being' => CharacterFactory::random(),
                    'talent' => TalentFactory::new(),
                ];
            }
        );

        BeingTalentFactory::createMany(
            15,
            function () {
                return [
                    'being' => NonPlayableCharacterFactory::random(),
                    'talent' => TalentFactory::new(),
                ];
            }
        );

        BeingTalentFactory::createMany(
            15,
            function () {
                return [
                    'being' => MonsterFactory::random(),
                    'talent' => TalentFactory::new(),
                ];
            }
        );

        // 4.4. Create Armaments
        ArmamentFactory::createOne([
            'name' => 'Armament Without owner',
            'game' => $mainGame,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
        ]);
        ArmamentFactory::createOne([
            'name' => 'Armament Monster',
            'game' => $mainGame,
            'being' => MonsterFactory::random(),
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
        ]);
        ArmamentFactory::createOne([
            'name' => 'Armament NPC',
            'game' => $mainGame,
            'being' => NonPlayableCharacterFactory::random(),
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
        ]);
        ArmamentFactory::createOne([
            'name' => 'Armament Red 1',
            'game' => $mainGame,
            'being' => $characterRed,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
        ]);
        ArmamentFactory::createOne([
            'name' => 'Armament Red 2',
            'game' => $mainGame,
            'being' => $characterRed,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
        ]);
        ArmamentFactory::createOne([
            'name' => 'Armament Green',
            'game' => $mainGame,
            'being' => $characterGreen,
            'spells' => SpellFactory::randomRange(0, 3),
            'skills' => SkillFactory::randomRange(0, 3),
        ]);

        $manager->flush();
    }
}
