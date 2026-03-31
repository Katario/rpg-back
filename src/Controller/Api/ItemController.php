<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\BeingItem;
use App\Entity\Item;
use App\Repository\BeingItemRepository;
use App\Repository\CharacterRepository;
use App\Repository\ItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ItemController extends AbstractController
{
    #[Route('/characters/{token}/items', name: 'api_character_item_add', methods: ['POST'])]
    public function add(
        string $token,
        Request $request,
        CharacterRepository $characterRepository,
        ItemRepository $itemRepository,
        BeingItemRepository $beingItemRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);

        if (empty($body['name'])) {
            return $this->json(['error' => 'Field "name" is required'], Response::HTTP_BAD_REQUEST);
        }

        $item = $itemRepository->findOneByName($body['name']);
        if (!$item) {
            $item = (new Item())
                ->setName($body['name'])
                ->setDescription($body['description'] ?? '')
                ->setValue(0)
                ->setWeight($body['weight'] ?? 0)
                ->setIsReady(true)
                ->setIsPrivate(false);
            $itemRepository->save($item);
        }

        $quantity = isset($body['quantity']) ? (int) $body['quantity'] : 1;

        $beingItem = $beingItemRepository->findOneByBeingAndItem($character, $item);
        if ($beingItem) {
            $beingItem->setQuantity($beingItem->getQuantity() + $quantity);
        } else {
            $beingItem = (new BeingItem())
                ->setBeing($character)
                ->setItem($item)
                ->setQuantity($quantity);
        }

        $beingItemRepository->save($beingItem);

        return $this->json(['id' => $item->getId(), 'quantity' => $beingItem->getQuantity()], Response::HTTP_OK);
    }

    #[Route('/characters/{token}/items/{itemId}', name: 'api_character_item_update', methods: ['PATCH'])]
    public function update(
        string $token,
        int $itemId,
        Request $request,
        CharacterRepository $characterRepository,
        ItemRepository $itemRepository,
        BeingItemRepository $beingItemRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $item = $itemRepository->find($itemId);

        if (!$item) {
            return $this->json(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        $beingItem = $beingItemRepository->findOneByBeingAndItem($character, $item);

        if (!$beingItem) {
            return $this->json(['error' => 'Item not found for this character'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);

        if (!isset($body['quantity'])) {
            return $this->json(['error' => 'Field "quantity" is required'], Response::HTTP_BAD_REQUEST);
        }

        $beingItem->setQuantity((int) $body['quantity']);
        $beingItemRepository->save($beingItem);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/characters/{token}/items/{itemId}', name: 'api_character_item_delete', methods: ['DELETE'])]
    public function delete(
        string $token,
        int $itemId,
        CharacterRepository $characterRepository,
        ItemRepository $itemRepository,
        BeingItemRepository $beingItemRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $item = $itemRepository->find($itemId);

        if (!$item) {
            return $this->json(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        $beingItem = $beingItemRepository->findOneByBeingAndItem($character, $item);

        if (!$beingItem) {
            return $this->json(['error' => 'Item not found for this character'], Response::HTTP_NOT_FOUND);
        }

        $beingItemRepository->delete($beingItem);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
