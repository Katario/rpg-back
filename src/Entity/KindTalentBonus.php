<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class KindTalentBonus extends KindBonus
{
    #[ORM\ManyToOne(targetEntity: Talent::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Talent $talent;

    public function getTalent(): Talent
    {
        return $this->talent;
    }

    public function setTalent(Talent $talent): static
    {
        $this->talent = $talent;

        return $this;
    }
}
