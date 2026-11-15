<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Repository\CharacterRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Makes a disabled character read-only on the API: any change to `/api/characters/{token}/...` (POST, PATCH,
 * DELETE…) is refused with a 403, whatever the access (character token or account). Reads stay allowed.
 *
 * Applied to every `api_character_*` route that has a `{token}`, so new write routes are covered too.
 * Runs after routing (priority 32) and the firewall (8).
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 0)]
final readonly class DisabledCharacterGuard
{
    public function __construct(
        private CharacterRepository $characterRepository,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || $request->isMethodSafe()) {
            return;
        }

        $route = $request->attributes->get('_route');
        $token = $request->attributes->get('token');
        if (!is_string($route) || !str_starts_with($route, 'api_character_') || !is_string($token)) {
            return;
        }

        if ($this->characterRepository->findOneByToken($token)?->isDisabled()) {
            $event->setResponse(new JsonResponse(['error' => 'This character is disabled.'], Response::HTTP_FORBIDDEN));
        }
    }
}
