<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\MappedSuperclass;

#[MappedSuperclass]
abstract class Encyclopedia
{
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    protected bool $isReady;
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    protected bool $isPrivate;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: true)]
    private ?User $createdBy = null;

    public function isReady(): bool
    {
        return $this->isReady;
    }

    public function setIsReady(bool $isReady): Encyclopedia
    {
        $this->isReady = $isReady;

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): Encyclopedia
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): Encyclopedia
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
