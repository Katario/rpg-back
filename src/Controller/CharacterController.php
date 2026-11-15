<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Character;
use App\Entity\Game;
use App\Factory\CharacterFactory;
use App\FormType\CharacterType;
use App\Repository\CharacterRepository;
use App\Repository\CharacterTemplateRepository;
use App\Security\Voter\CharacterVoter;
use App\Security\Voter\GameVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
class CharacterController
{
    public function __construct(
        public Environment $twig,
        public FormFactoryInterface $formFactory,
        public UrlGeneratorInterface $router,
        public Security $security,
        public CharacterRepository $characterRepository,
    ) {
    }

    #[Route('/games/{gameId}/characters/{id}', name: 'show_character', requirements: ['gameId' => '\d+', 'id' => '\d+'], methods: ['GET'])]
    #[IsGranted(CharacterVoter::VIEW, 'character')]
    public function showCharacter(
        #[MapEntity(id: 'id')] Character $character,
    ): Response {
        return new Response(
            $this->twig->render('character/show.html.twig', [
                'character' => $character,
            ])
        );
    }

    #[Route('/games/{gameId}/characters/{id}/edit',
        name: 'edit_character',
        requirements: ['gameId' => '\d+', 'id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted(CharacterVoter::EDIT, 'character')]
    public function editCharacter(
        Request $request,
        #[MapEntity(id: 'id')] Character $character,
    ): Response|RedirectResponse {
        $form = $this->formFactory->create(
            CharacterType::class,
            $character,
            [
                'gameId' => $character->getGame()->getId(),
                'currentOwnerId' => $character->getUser()?->getId(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Character $character */
            $character = $form->getData();

            $this->characterRepository->save($character);

            return new RedirectResponse($this->router->generate('show_game',
                ['id' => $character->getGame()->getId()]
            ));
        }

        return new Response(
            $this->twig->render('character/edit.html.twig', [
                'form' => $form->createView(),
            ])
        );
    }

    #[Route('/games/{gameId}/characters/{id}/delete',
        name: 'delete_character',
        requirements: ['gameId' => '\d+', 'id' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted(CharacterVoter::DELETE, 'character')]
    public function deleteCharacter(
        #[MapEntity(id: 'id')] Character $character,
    ): Response {
        $gameId = $character->getGame()->getId();
        $this->characterRepository->delete($character);

        return new RedirectResponse($this->router->generate('show_game', ['id' => $gameId]));
    }

    /**
     * Disables or enables a character (a disabled sheet is read-only on the API, see docs/game.md).
     */
    #[Route('/games/{gameId}/characters/{id}/{state}',
        name: 'toggle_character',
        requirements: ['gameId' => '\d+', 'id' => '\d+', 'state' => 'disable|enable'],
        methods: ['POST']
    )]
    #[IsGranted(CharacterVoter::EDIT, 'character')]
    #[IsCsrfTokenValid(new Expression('"character-toggle-" ~ args["character"].getId()'))]
    public function toggleCharacter(
        #[MapEntity(id: 'id')] Character $character,
        string $state,
    ): RedirectResponse {
        'disable' === $state ? $character->disable() : $character->enable();
        $this->characterRepository->save($character);

        return new RedirectResponse($this->router->generate('show_game', ['id' => $character->getGame()->getId()]));
    }

    #[Route('/games/{gameId}/characters/generate',
        name: 'generate_character',
        requirements: ['gameId' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[IsGranted(GameVoter::MANAGE, 'game')]
    public function generateCharacter(
        Request $request,
        #[MapEntity(mapping: ['gameId' => 'id'])] Game $game,
        CharacterFactory $characterFactory,
        CharacterTemplateRepository $characterTemplateRepository,
    ): Response|RedirectResponse {
        if ($request->get('characterTemplateId')) {
            $characterTemplate = $characterTemplateRepository->find($request->get('characterTemplateId'));
            $character = $characterFactory->createOneFromCharacterTemplate($characterTemplate);
            $character->setGame($game);
        } else {
            $character = new Character();
            $character->setGame($game);
        }

        $form = $this->formFactory->create(
            CharacterType::class,
            $character, [
                'gameId' => $game->getId(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Character $character */
            $character = $form->getData();

            $this->characterRepository->save($character);

            return new RedirectResponse($this->router->generate('show_game',
                ['id' => $character->getGame()->getId()]
            ));
        }

        return new Response(
            $this->twig->render('character/generate.html.twig', [
                'form' => $form->createView(),
            ])
        );
    }
}
