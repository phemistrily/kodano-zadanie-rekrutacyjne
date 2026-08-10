<?php

namespace App\Tests\Unit\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Category\Entity\Category;
use App\Domain\Notification\Notification;
use App\Domain\Notification\NotifierInterface;
use App\Domain\Product\Entity\Product;
use App\Infrastructure\ApiPlatform\State\ProductNotificationProcessor;
use PHPUnit\Framework\TestCase;

final class ProductNotificationProcessorTest extends TestCase
{
    public function testNotifiesAfterProductIsPersisted(): void
    {
        $product = (new Product())->setName('Laptop')->setPrice('1999.99');
        $product->addCategory((new Category())->setCode('ELEC'));

        $inner = $this->createMock(ProcessorInterface::class);
        $inner->expects(self::once())->method('process')->willReturn($product);

        $captured = null;
        $notifier = $this->createMock(NotifierInterface::class);
        $notifier->expects(self::once())
            ->method('notify')
            ->with(self::callback(static function (Notification $notification) use (&$captured): bool {
                $captured = $notification;

                return true;
            }));

        $result = (new ProductNotificationProcessor($inner, $notifier))->process($product, new Post());

        self::assertSame($product, $result);
        self::assertInstanceOf(Notification::class, $captured);
        self::assertStringContainsString('Laptop', $captured->getSubject());
        self::assertSame('Laptop', $captured->getContext()['name']);
        self::assertSame('1999.99', $captured->getContext()['price']);
        self::assertSame(['ELEC'], $captured->getContext()['categories']);
    }

    public function testDoesNotNotifyWhenResultIsNotAProduct(): void
    {
        $category = (new Category())->setCode('ELEC');

        $inner = $this->createStub(ProcessorInterface::class);
        $inner->method('process')->willReturn($category);

        $notifier = $this->createMock(NotifierInterface::class);
        $notifier->expects(self::never())->method('notify');

        $result = (new ProductNotificationProcessor($inner, $notifier))->process($category, new Post());

        self::assertSame($category, $result);
    }
}
