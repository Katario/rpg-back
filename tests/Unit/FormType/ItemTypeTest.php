<?php

declare(strict_types=1);

namespace App\Tests\Unit\FormType;

use App\Entity\Item;
use App\FormType\ItemType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(ItemType::class)]
#[AllowMockObjectsWithoutExpectations]
class ItemTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'name' => 'Healing Potion',
            'description' => 'This is an Healing Potion.',
            'value' => 10,
            'weight' => 10,
            'note' => 'This is the note that I want to add.',
            'isReady' => true,
            'isPrivate' => false,
        ];

        $model = new Item();
        $form = $this->factory->create(ItemType::class, $model);

        $expectedItem = new Item();
        $expectedItem->setName('Healing Potion')
            ->setDescription('This is an Healing Potion.')
            ->setValue(10)
            ->setWeight(10)
            ->setNote('This is the note that I want to add.')
            ->setIsReady(true)
            ->setIsPrivate(false)
        ;

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedItem, $model);
    }
}
