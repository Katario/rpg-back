<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Game
{
    use HasDateTimeTrait;
    use HasNoteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(type: 'string')]
    private string $name;
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'gameMasters')]
    #[JoinColumn(name: 'game_master', referencedColumnName: 'id')]
    private User $gameMaster;
    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'game')]
    private Collection|array $characters;
    #[ORM\OneToMany(targetEntity: Equipment::class, mappedBy: 'game')]
    private Collection|array $equipments;
    #[ORM\OneToMany(targetEntity: Monster::class, mappedBy: 'game')]
    private Collection|array $monsters;
    #[ORM\OneToMany(targetEntity: NonPlayableCharacter::class, mappedBy: 'game')]
    private Collection|array $nonPlayableCharacters;
    /** @var Collection<int, GameMembership> */
    #[ORM\OneToMany(targetEntity: GameMembership::class, mappedBy: 'game', cascade: ['persist'], orphanRemoval: true)]
    private Collection $memberships;
    // Null: the game is closed, nobody can join it. Stored normalized: 8 uppercase characters, no dash.
    #[ORM\Column(type: 'string', length: 8, unique: true, nullable: true)]
    private ?string $inviteCode = null;
    // Archived games are kept (no data is ever deleted) but hidden and closed to new players.
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    public function __construct()
    {
        $this->characters = new ArrayCollection();
        $this->equipments = new ArrayCollection();
        $this->monsters = new ArrayCollection();
        $this->nonPlayableCharacters = new ArrayCollection();
        $this->memberships = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Game
    {
        $this->name = $name;

        return $this;
    }

    public function getGameMaster(): User
    {
        return $this->gameMaster;
    }

    public function setGameMaster(User $gameMaster): Game
    {
        $this->gameMaster = $gameMaster;

        return $this;
    }

    public function getCharacters(): Collection|array
    {
        return $this->characters;
    }

    public function setCharacters(Collection|array $characters): Game
    {
        $this->characters = $characters;

        return $this;
    }

    public function addCharacter(Character $character): Game
    {
        if (!$this->getCharacters()->contains($character)) {
            $this->characters->add($character);
        }

        return $this;
    }

    public function removeCharacter(Character $character): Game
    {
        if ($this->getCharacters()->contains($character)) {
            $this->characters->removeElement($character);
        }

        return $this;
    }

    public function getEquipments(): Collection|array
    {
        return $this->equipments;
    }

    public function setEquipments(Collection|array $equipments): Game
    {
        $this->equipments = $equipments;

        return $this;
    }

    public function addArmament(Equipment $armament): Game
    {
        if (!$this->getEquipments()->contains($armament)) {
            $this->equipments->add($armament);
        }

        return $this;
    }

    public function removeArmament(Equipment $armament): Game
    {
        if ($this->getEquipments()->contains($armament)) {
            $this->equipments->removeElement($armament);
        }

        return $this;
    }

    public function getMonsters(): Collection|array
    {
        return $this->monsters;
    }

    public function addMonster(Monster $monster): Game
    {
        if (!$this->getMonsters()->contains($monster)) {
            $this->monsters->add($monster);
        }

        return $this;
    }

    public function removeMonster(Monster $monster): Game
    {
        if ($this->getMonsters()->contains($monster)) {
            $this->monsters->removeElement($monster);
        }

        return $this;
    }

    public function getNonPlayableCharacters(): Collection|array
    {
        return $this->nonPlayableCharacters;
    }

    public function addNonPlayableCharacter(NonPlayableCharacter $nonPlayableCharacter): Game
    {
        if (!$this->getNonPlayableCharacters()->contains($nonPlayableCharacter)) {
            $this->nonPlayableCharacters->add($nonPlayableCharacter);
        }

        return $this;
    }

    public function removeNonPlayableCharacter(NonPlayableCharacter $nonPlayableCharacter): Game
    {
        if ($this->getNonPlayableCharacters()->contains($nonPlayableCharacter)) {
            $this->nonPlayableCharacters->removeElement($nonPlayableCharacter);
        }

        return $this;
    }

    public function isGameMaster(User $user): bool
    {
        return self::isSameUser($this->gameMaster, $user);
    }

    /**
     * @return Collection<int, GameMembership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function hasMember(User $user): bool
    {
        return null !== $this->findMembership($user);
    }

    /**
     * Idempotent: joining twice returns the existing membership.
     */
    public function addMember(User $user, \DateTimeImmutable $now = new \DateTimeImmutable()): GameMembership
    {
        if ($this->isGameMaster($user)) {
            throw new \LogicException('The game master cannot join their own game as a player.');
        }

        $membership = $this->findMembership($user);
        if (null === $membership) {
            $membership = new GameMembership($this, $user, $now);
            $this->memberships->add($membership);
        }

        return $membership;
    }

    /**
     * Deletes the membership only (orphan removal): the player's characters are left untouched.
     */
    public function removeMember(User $user): void
    {
        $membership = $this->findMembership($user);
        if (null !== $membership) {
            $this->memberships->removeElement($membership);
        }
    }

    public function getInviteCode(): ?string
    {
        return $this->inviteCode;
    }

    /**
     * @param string|null $inviteCode a normalized code, or null to close the game
     */
    public function setInviteCode(?string $inviteCode): Game
    {
        $this->inviteCode = $inviteCode;

        return $this;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function isArchived(): bool
    {
        return null !== $this->archivedAt;
    }

    /**
     * Also closes the game: an archived game cannot be joined.
     */
    public function archive(\DateTimeImmutable $now = new \DateTimeImmutable()): Game
    {
        $this->archivedAt ??= $now;
        $this->inviteCode = null;

        return $this;
    }

    public function unarchive(): Game
    {
        $this->archivedAt = null;

        return $this;
    }

    private function findMembership(User $user): ?GameMembership
    {
        foreach ($this->memberships as $membership) {
            if (self::isSameUser($membership->getUser(), $user)) {
                return $membership;
            }
        }

        return null;
    }

    // Same object, or same database row (two instances of one user loaded separately).
    private static function isSameUser(User $a, User $b): bool
    {
        return $a === $b || (null !== $a->getId() && $a->getId() === $b->getId());
    }
}
