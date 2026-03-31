<?php

declare(strict_types=1);

namespace App\Twig\Components\Molecule;

use App\Entity\Equipment;
use App\Entity\EquipmentTemplate;
use App\Entity\Game;
use App\Repository\EquipmentRepository;
use App\Repository\EquipmentTemplateRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class ArmamentBlock
{
    use DefaultActionTrait;

    #[LiveProp(writable: false)]
    public ?Game $game;

    #[LiveProp(writable: true)]
    public string $query = '';

    public function __construct(
        public EquipmentRepository $armamentRepository,
        public EquipmentTemplateRepository $armamentTemplateRepository,
    ) {
    }

    /**
     * @return Equipment[]
     */
    public function getArmaments(): array
    {
        if (null === $this->game) {
            return [];
        }

        return $this->armamentRepository->findByGameBySearch(
            $this->game->getId(), '', 12
        );
    }

    /**
     * @return EquipmentTemplate[]
     */
    public function getArmamentTemplates(): array
    {
        if (null === $this->game) {
            return [];
        }

        return $this->armamentTemplateRepository->findBySearch(
            $this->query, 12
        );
    }
}
