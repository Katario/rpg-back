<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Equipment;
use App\Entity\Game;
use App\Entity\Weapon;
use App\Factory\ArmorFactory;
use App\Factory\WeaponFactory;
use App\FormType\ArmamentType;
use App\Repository\EquipmentRepository;
use App\Repository\EquipmentTemplateRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

#[AsController]
class ArmamentController
{
    public function __construct(
        public Environment $twig,
        public readonly FormFactoryInterface $formFactory,
        private readonly RouterInterface $router,
        public EquipmentRepository $armamentRepository,
    ) {
    }

    #[Route('/armaments/{id}', name: 'show_armament', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showArmament(
        int $id,
    ): Response {
        $armament = $this->armamentRepository->find($id);

        return new Response(
            $this->twig->render('armament/show.html.twig', [
                'armament' => $armament,
            ])
        );
    }

    #[Route('/armaments/{id}/edit',
        name: 'edit_armament',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function editArmament(
        Request $request,
        int $id,
    ): Response|RedirectResponse {
        $armament = $this->armamentRepository->find($id);

        if (!$armament) {
            throw new NotFoundHttpException();
        }

        $form = $this->formFactory->create(
            ArmamentType::class,
            $armament
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Equipment $armament */
            $armament = $form->getData();

            $this->armamentRepository->save($armament);

            return new RedirectResponse($this->router->generate('show_game', [
                'id' => $armament->getGame()->getId(),
            ]));
        }

        return new Response(
            $this->twig->render('armament/edit.html.twig', [
                'form' => $form->createView(),
                'armament' => $armament,
            ])
        );
    }

    // @TODO: should be updated to use DELETE HTTP method, but must use AJAX in front to do so
    #[Route('/armaments/{id}/delete',
        name: 'delete_armament',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function deleteArmament(
        int $id,
    ): Response {
        /** @var Equipment $armament */
        $armament = $this->armamentRepository->find($id);

        if (!$armament) {
            throw new NotFoundHttpException();
        }

        $gameId = $armament->getGame()->getId();
        $this->armamentRepository->delete($armament);

        return new RedirectResponse($this->router->generate('show_game', [
            'id' => $gameId,
        ]));
    }

    #[Route('/games/{gameId}/armaments/generate',
        name: 'generate_armament',
        requirements: ['gameId' => '\d+'],
        methods: ['GET', 'POST']),]
    public function generateArmament(
        Request $request,
        #[MapEntity(mapping: ['gameId' => 'id'])] Game $game,
        WeaponFactory $weaponFactory,
        ArmorFactory $armorFactory,
        EquipmentTemplateRepository $armamentTemplateRepository,
    ): Response|RedirectResponse {
        if ($request->get('armamentTemplateId')) {
            $armamentTemplate = $armamentTemplateRepository->find($request->get('armamentTemplateId'));
            $factory = $armamentTemplate->getCategory() === 'weapon' ? $weaponFactory : $armorFactory;
            $armament = $factory->createOneFromEquipmentTemplate($armamentTemplate);
            $armament->setGame($game);
        } else {
            $armament = new Weapon();
            $armament->setGame($game);
        }

        $form = $this->formFactory->create(ArmamentType::class, $armament);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Equipment $character */
            $armament = $form->getData();

            $this->armamentRepository->save($armament);

            return new RedirectResponse($this->router->generate(
                'show_armament', [
                    'id' => $armament->getId(),
                ]
            ));
        }

        return new Response(
            $this->twig->render('armament/generate.html.twig', [
                'form' => $form->createView(),
            ]),
        );
    }
}
