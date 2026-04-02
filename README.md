# RPG-APP
NOTE: this is still in Alpha.
Welcome! This application is a tool to help Game Masters and Players to create their Role Playing Games.

## Installation:
To run the project, you need to have Docker installed (version 28+ are supported, probably Docker version 21 to 27 are also, but not sure.)
   1. First, you need to clone the repo on your computer. Please `git clone `
   2. Copy the `.env` to a `.env.local`, and fill the different keys.
   3. Pull the Docker image, build the container and log into it: `make start`
   4. Create the database, the schema, and add fixtures: `make reset-database`
   5. You should now be able to access the website at `http://localhost:9984`

Note: to interact with the project, I recommend connecting to the *-php container and starting every command in it.
The only commands you should do outside the container should be `make` and `git` commands.


## Stack used:
- PHP 8.3 (will be updated to PHP 8.4 later)
- Symfony: as a framework, support the application.
- Symfony UX: for the front, I'm using a mix of Twig and Stimulus, as suggested in the Symfony UX initiative. If you still don't know about  it, [check it out!](https://ux.symfony.com/)
- Composer: Composer is a dependency Manager for PHP. If you don't know it... You have a lot to catch up with PHP :D 
- Docker: Docker is the container application. You should now about it by now, and if it's not the case, go check it out. I'm using it to handle a local environment easily. I plan to deploy with it in the future, but not right now.
- PostGreSQL: An object-relational-oriented Database.


## Architecture approach:
In the Back environment, I choose to start with MVC: I started a project which is suppose to be displaying informations,
and I'm not sure where it's heading. If it is getting more complex, like adding a live board system or else, then I'll
refacto the project to another structure.

In the Front environment, I'm trying to adopt [an "Atomic" approach](https://atomicdesign.bradfrost.com/chapter-2/).
The idea is to have multiple components, split in size and goal:
- An Atom, which is the smallest component possible. It doesn't contains any business logic, and should contain "Variants", which are different behaviors according to passed parameters, but close enough in meaning to be gathered as a simple Atom. Example : a Button component
- A Molecule, which is a gathering of only Atoms. It should contains the minimum possible of business logic, which will mostly decide when to display an atom, and which parameters to pass to it. Example: A Block
- An Organism (or a page), which is the gathering of multiple Atoms and Molecules, to display an ensemble. It contains the most of our display logic!


## Tools:
#### [MakeFile](https://www.gnu.org/software/make/manual/make.html)
I've set up a Makefile to help enter the most common commands.

#### [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer)
PHP-CS-Fixer is a tool that analyse / fix the code by applying rules, like PSR or custom ones. On this project, we currently
only use PSR & Symfony rules. Check php-cs-fixer.dist.php for more informations.
- To list all files that needs to be fixed: `vendor/bin/php-cs-fixer check`
- To fix files according to configured rules: `vendor/bin/php-cs-fixer fix`

Note: this tool should be applied before EACH commit, so you should configure your git hooks. It will also run in the pipeline, to check if everything has been made properly.

#### [PHPUnit](https://github.com/sebastianbergmann/phpunit/)
> PHPUnit is a programmer-oriented testing framework for PHP. It is an instance of the xUnit architecture for unit testing frameworks.

- To run the tests: `vendor/bin/phpunit`

#### [PHPStan](https://phpstan.org/)
> PHPStan is a static analisys tool. It runs the code, without actually running the code.

In this project, this tool is used to look for bugs in the code and to maintain an even Higher quality of code. I'm
using the current maximum level to set (lvl 10), and while I know it's not applied yet, there is a `phpstan-baseline.neon`
that list every error to do.
My current rule is to get used to PHPStan lvl 10, and try to decrease on each commit the number of errors in the file.

- To run PHPStan: `vendor/bin/phpstan`
- To run PHPStan and generate the baseline for it: `vendor/bin/phpstan --generate-baseline`


## Most useful Commands:
- `make start`: Down the container, then build it, create it.
- `make watch`: Down the container, then build it, create it and open a bash in the php container. Then, start tailwind with watch option.
- `make stop`: Down the container
- `make test`: Start the unit tests
- `make phpstan`: Start PHP Stan, and generate the baseline
- `make cs-fix`: Fix every file with the current php-cs-fixer configuration


## Git Workflow process:
The Gitflow process is Straight Forward for now, as I'm the only developer, and there are no Pipeline tools.
The goal would be to adopt a Git-Forkflow system, but adapted to the fact I'm the only developer. More on that later.  


## [Agile Methodology](https://agilemanifesto.org/):
This Project follows the ScrumBan Methodology. This is a method directly inspired from the Agility manifest.
Since We're two for now, and one of us is not a developer, we need to organise in a simple way. We built a board (A Trello)
to follow the evolution of the project, and follow the ScrumBan Methodology:
- The process contains five steps: "to do", "ongoing", "test", "deploy", "done"
- Each task is clearly define, as a DoD and a priority.
- Each task should go from left to right, across all the different steps of the process
- If a task is failing a step (test KO), a new task linked to the old one should be created

Aside from the Process Steps, there are also some useful columns: A "Backlog", "Abandoned"...


## Summary of all the Concepts in the App
Since this is an app about RPG, I'll assume you have all the basic knowledge of RPG stuff, like GM or Player!

#### Character:
A character may be played by a player.
Ex: Epolas Eret'Matkin

#### Non-Playable Character:
A character that can't be incarnated by a player, is used by the Game Master, and can do some actions by itself.
Ex: Al'Ratab

#### Encyclopedia
The Encyclopedia contains every "static" concepts of the game (Like Items, Skill or Spells) and every templates for
"dynamic" concepts (like MonsterTemplate, or ArmamentTemplates).
It is common to every GM.

#### Item:
An item may be used by a player to do an action.
Ex: A rock, a potion or a key

#### Mastery:
A mastery may be unlocked on a Mastery Tree.
Ex: Far-Sight

#### Monster:
A creature that may attack the player.
Ex: A Goblin

#### Armament:
A type of item, that may be used in battle to fight or protect.
Ex: A wooden sword or a wooden helmet

#### Skill:
A skill is attached to a Weapon, an Item or a Character. It is used as an action.
Ex: Slicing Attack

#### Spell:
A spell may be cast by some Character, NonPlayableCharacter and Monsters. It costs mana and require a dedicated type of magic and level to unlock it.
Ex: Fireball

#### Talent:
A character have many talent that are used to act.
Ex: Archery

## How to update ?
You currently can't. Please contact me via my LinkedIn profile.



TODO:
1. When a playable character is created, you can generate a link + a hash to store in that character row.
2. When a player access the route, it sends the hash, that is used to retrieve the Character.
3. Retrieve the Character = Sending all the data from it.

