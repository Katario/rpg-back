<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table(name: '"user"')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use HasDateTimeTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'gameMaster')]
    private Collection|array $gameMasters;

    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'user')]
    private Collection|array $characters;

    public function __construct()
    {
        $this->gameMasters = new ArrayCollection();
        $this->characters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    // TODO: Is needed?
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->username ?? $this->email;
    }

    // This is a representation of the user
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    // For temporary and sensitive data about the user
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getGameMasters(): ArrayCollection
    {
        return $this->gameMasters;
    }

    public function setGameMasters(ArrayCollection $gameMasters): User
    {
        $this->gameMasters = $gameMasters;

        return $this;
    }

    public function addGameMaster(Game $game): User
    {
        if (!$this->getGameMasters()->contains($game)) {
            $this->gameMasters->add($game);
        }

        return $this;
    }

    public function removeGame(Game $game): User
    {
        if ($this->getGameMasters()->contains($game)) {
            $this->gameMasters->removeElement($game);
        }

        return $this;
    }

    public function getCharacters(): ArrayCollection
    {
        return $this->characters;
    }

    public function setCharacters(ArrayCollection $characters): User
    {
        $this->characters = $characters;

        return $this;
    }

    public function addCharacter(Character $character): User
    {
        if (!$this->getCharacters()->contains($character)) {
            $this->characters->add($character);
        }

        return $this;
    }

    public function removeCharacter(Character $character): User
    {
        if ($this->getCharacters()->contains($character)) {
            $this->characters->removeElement($character);
        }

        return $this;
    }
}
