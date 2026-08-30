<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Calculator\LoadCalculator;
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
    #[Route('/items', name: 'api_items_list', methods: ['GET'])]
    public function list(ItemRepository $itemRepository): JsonResponse
    {
        $items = $itemRepository->findAll();

        return $this->json(array_map(fn (Item $item) => $this->serializeItem($item), $items));
    }

    #[Route('/characters/{token}/items', name: 'api_character_item_add', methods: ['POST'])]
    public function add(
        string $token,
        Request $request,
        CharacterRepository $characterRepository,
        ItemRepository $itemRepository,
        BeingItemRepository $beingItemRepository,
        LoadCalculator $loadCalculator,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);

        if (empty($body['itemId'])) {
            return $this->json(['error' => 'Field "itemId" is required'], Response::HTTP_BAD_REQUEST);
        }

        $item = $itemRepository->find((int) $body['itemId']);

        if (!$item) {
            return $this->json(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        if ($beingItemRepository->findOneByBeingAndItem($character, $item)) {
            return $this->json(['error' => 'Item already attached'], Response::HTTP_CONFLICT);
        }

        $quantity = isset($body['quantity']) ? max(1, (int) $body['quantity']) : 1;

        $beingItem = (new BeingItem())
            ->setBeing($character)
            ->setItem($item)
            ->setQuantity($quantity);
        $beingItemRepository->save($beingItem);

        return $this->json(
            $this->serializeItem($item) + [
                'quantity' => $beingItem->getQuantity(),
                'currentLoadPoints' => $loadCalculator->computeCurrentLoadPoints($character),
            ],
            Response::HTTP_CREATED,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeItem(Item $item): array
    {
        return [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'description' => $item->getDescription(),
            'weight' => $item->getWeight(),
            'value' => $item->getValue(),
        ];
    }

    #[Route('/characters/{token}/items/{itemId}', name: 'api_character_item_update', methods: ['PATCH'])]
    public function update(
        string $token,
        int $itemId,
        Request $request,
        CharacterRepository $characterRepository,
        ItemRepository $itemRepository,
        BeingItemRepository $beingItemRepository,
        LoadCalculator $loadCalculator,
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

        return $this->json([
            'quantity' => $beingItem->getQuantity(),
            'currentLoadPoints' => $loadCalculator->computeCurrentLoadPoints($character),
        ], Response::HTTP_OK);
    }

    #[Route('/characters/{token}/items/{itemId}', name: 'api_character_item_delete', methods: ['DELETE'])]
    public function delete(
        string $token,
        int $itemId,
        CharacterRepository $characterRepository,
        ItemRepository $itemRepository,
        BeingItemRepository $beingItemRepository,
        LoadCalculator $loadCalculator,
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

        return $this->json([
            'currentLoadPoints' => $loadCalculator->computeCurrentLoadPoints($character),
        ], Response::HTTP_OK);
    }
}
