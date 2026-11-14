<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Monster;
use App\Factory\MonsterFactory;
use App\FormType\MonsterType;
use App\Repository\MonsterRepository;
use App\Repository\MonsterTemplateRepository;
use App\Security\Voter\GameVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
class MonsterController
{
    public function __construct(
        public readonly MonsterRepository $monsterRepository,
        public readonly Environment $twig,
        public readonly UrlGeneratorInterface $router,
        public readonly FormFactoryInterface $formFactory,
    ) {
    }

    #[Route('/monsters/{id}', name: 'show_monster', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(GameVoter::MANAGE, 'monster')]
    public function showMonster(
        #[MapEntity(id: 'id')] Monster $monster,
    ): Response {
        return new Response(
            $this->twig->render('monster/show.html.twig', [
                'monster' => $monster,
            ])
        );
    }

    #[Route('/monsters/{id}/edit',
        name: 'edit_monster',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted(GameVoter::MANAGE, 'monster')]
    public function editMonster(
        Request $request,
        #[MapEntity(id: 'id')] Monster $monster,
    ): Response|RedirectResponse {
        $form = $this->formFactory->create(
            MonsterType::class,
            $monster,
            [
                'gameId' => $monster->getGame()->getId(),
                'monsterId' => $monster->getId(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Monster $monster */
            $monster = $form->getData();

            $this->monsterRepository->save($monster);

            return new RedirectResponse($this->router->generate('show_game',
                ['id' => $monster->getGame()->getId()]
            ));
        }

        return new Response(
            $this->twig->render('monster/edit.html.twig', [
                'form' => $form->createView(),
            ])
        );
    }

    #[Route('/monsters/{id}/delete',
        name: 'delete_monster',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted(GameVoter::MANAGE, 'monster')]
    public function deleteMonster(
        #[MapEntity(id: 'id')] Monster $monster,
    ): Response {
        $gameId = $monster->getGame()->getId();
        $this->monsterRepository->delete($monster);

        return new RedirectResponse($this->router->generate('show_game',
            ['id' => $gameId]
        ));
    }

    #[Route('/games/{gameId}/monsters/generate',
        name: 'generate_monster',
        requirements: ['gameId' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted(GameVoter::MANAGE, 'game')]
    public function generateMonster(
        Request $request,
        #[MapEntity(mapping: ['gameId' => 'id'])] Game $game,
        MonsterTemplateRepository $monsterTemplateRepository,
        MonsterFactory $monsterFactory,
    ): Response|RedirectResponse {
        if ($request->get('monsterTemplateId')) {
            $monsterTemplate = $monsterTemplateRepository->find($request->get('monsterTemplateId'));
            $monster = $monsterFactory->createOneFromMonsterTemplate($monsterTemplate);
            $monster->setGame($game);
        } else {
            $monster = new Monster();
            $monster->setGame($game);
        }
        $form = $this->formFactory->create(
            MonsterType::class,
            $monster, [
                'gameId' => $game->getId(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Monster $monster */
            $monster = $form->getData();

            $this->monsterRepository->save($monster);

            return new RedirectResponse($this->router->generate('show_game',
                ['id' => $monster->getGame()->getId()]
            ));
        }

        return new Response(
            $this->twig->render('monster/generate.html.twig', [
                'form' => $form->createView(),
            ])
        );
    }
}
