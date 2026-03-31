<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\KindRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KindRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Kind extends Encyclopedia
{
    use HasDateTimeTrait;
    use HasNoteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\OneToMany(targetEntity: KindBonus::class, mappedBy: 'kind', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $bonuses;

    public function __construct()
    {
        $this->bonuses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Kind
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Kind
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, KindBonus>
     */
    public function getBonuses(): Collection
    {
        return $this->bonuses;
    }

    public function addBonus(KindBonus $bonus): static
    {
        if (!$this->bonuses->contains($bonus)) {
            $bonus->setKind($this);
            $this->bonuses->add($bonus);
        }

        return $this;
    }

    public function removeBonus(KindBonus $bonus): static
    {
        $this->bonuses->removeElement($bonus);

        return $this;
    }
}
