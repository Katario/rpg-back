<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Game;
use App\Entity\NonPlayableCharacter;
use App\Factory\NonPlayableCharacterFactory;
use App\FormType\NonPlayableCharacterType;
use App\Repository\NonPlayableCharacterRepository;
use App\Repository\NonPlayableCharacterTemplateRepository;
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
class NonPlayableCharacterController
{
    public function __construct(
        public readonly NonPlayableCharacterRepository $nonPlayableCharacterRepository,
        public readonly Environment $twig,
        public readonly UrlGeneratorInterface $router,
        public readonly FormFactoryInterface $formFactory,
    ) {
    }

    #[Route('/npcs/{id}', name: 'show_non_playable_character', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(GameVoter::MANAGE, 'nonPlayableCharacter')]
    public function showNonPlayableCharacter(#[MapEntity(id: 'id')] NonPlayableCharacter $nonPlayableCharacter): Response
    {
        return new Response(
            $this->twig->render('non_playable_character/show.html.twig', [
                'nonPlayableCharacter' => $nonPlayableCharacter,
            ])
        );
    }

    #[Route('/npcs/{id}/edit',
        name: 'edit_non_playable_character',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted(GameVoter::MANAGE, 'nonPlayableCharacter')]
    public function editNonPlayableCharacter(
        Request $request,
        #[MapEntity(id: 'id')] NonPlayableCharacter $nonPlayableCharacter,
    ): Response|RedirectResponse {
        $form = $this->formFactory->create(
            NonPlayableCharacterType::class,
            $nonPlayableCharacter,
            [
                'gameId' => $nonPlayableCharacter->getGame()->getId(),
                'nonPlayableCharacterId' => $nonPlayableCharacter->getId(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var NonPlayableCharacter $nonPlayableCharacter */
            $nonPlayableCharacter = $form->getData();

            $this->nonPlayableCharacterRepository->save($nonPlayableCharacter);

            return new RedirectResponse($this->router->generate('show_game', [
                'id' => $nonPlayableCharacter->getGame()->getId(),
            ]));
        }

        return new Response(
            $this->twig->render('non_playable_character/edit.html.twig', [
                'form' => $form->createView(),
            ])
        );
    }

    // @TODO: should be updated to use DELETE HTTP method, but must use AJAX in front to do so
    #[Route('/npcs/{id}/delete',
        name: 'delete_non_playable_character',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted(GameVoter::MANAGE, 'nonPlayableCharacter')]
    public function deleteNonPlayableCharacter(
        #[MapEntity(id: 'id')] NonPlayableCharacter $nonPlayableCharacter,
    ): Response {
        $gameId = $nonPlayableCharacter->getGame()->getId();
        $this->nonPlayableCharacterRepository->delete($nonPlayableCharacter);

        return new RedirectResponse($this->router->generate('show_game', [
            'id' => $gameId,
        ]));
    }

    #[Route('/games/{gameId}/npcs/generate',
        name: 'generate_non_playable_character',
        methods: ['GET', 'POST']
    )]
    #[IsGranted(GameVoter::MANAGE, 'game')]
    public function generateNonPlayableCharacter(
        Request $request,
        #[MapEntity(mapping: ['gameId' => 'id'])] Game $game,
        NonPlayableCharacterTemplateRepository $nonPlayableCharacterTemplateRepository,
        NonPlayableCharacterFactory $nonPlayableCharacterFactory,
    ): Response|RedirectResponse {
        if ($request->get('nonPlayableCharacterTemplateId')) {
            $nonPlayableCharacterTemplate = $nonPlayableCharacterTemplateRepository->find($request->get('nonPlayableCharacterTemplateId'));
            $nonPlayableCharacter = $nonPlayableCharacterFactory->createOneFromNonPlayableCharacterTemplate($nonPlayableCharacterTemplate);
            $nonPlayableCharacter->setGame($game);
        } else {
            $nonPlayableCharacter = new NonPlayableCharacter();
            $nonPlayableCharacter->setGame($game);
        }

        $form = $this->formFactory->create(
            NonPlayableCharacterType::class,
            $nonPlayableCharacter, [
                'gameId' => $game->getId(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var NonPlayableCharacter $nonPlayableCharacter */
            $nonPlayableCharacter = $form->getData();

            $this->nonPlayableCharacterRepository->save($nonPlayableCharacter);

            return new RedirectResponse($this->router->generate('show_non_playable_character',
                ['id' => $nonPlayableCharacter->getId()]
            ));
        }

        return new Response(
            $this->twig->render('non_playable_character/generate.html.twig', [
                'form' => $form->createView(),
            ])
        );
    }
}
