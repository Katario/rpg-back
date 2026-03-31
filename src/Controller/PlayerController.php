<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CharacterRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
class PlayerController
{
    public function __construct(
        public Environment $twig,
    ) {
    }

    #[Route('/player/{token}', name: 'show_player_character_by_token', methods: ['GET'])]
    public function showCharacterByToken(
        CharacterRepository $characterRepository,
        string $token,
    ): Response {
        if (!$this->isMd5($token)) {
            throw new NotFoundHttpException('The token is not valid');
        }

        $character = $characterRepository->findOneBy([
            'token' => $token,
        ]);

        if (!$character) {
            throw new NotFoundHttpException('The character doesn\'t exists!');
        }

        return new Response(
            $this->twig->render('player/character/show.html.twig', [
                'character' => $character,
            ])
        );
    }

    private function isMd5(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', $token);
    }
}
