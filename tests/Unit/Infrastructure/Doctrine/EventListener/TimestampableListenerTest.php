<?php

namespace App\Tests\Unit\Infrastructure\Doctrine\EventListener;

use App\Domain\Product\Entity\Product;
use App\Infrastructure\Doctrine\EventListener\TimestampableListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;

final class TimestampableListenerTest extends TestCase
{
    public function testPrePersistSetsBothTimestamps(): void
    {
        $product = new Product();
        $event = new PrePersistEventArgs($product, $this->createStub(EntityManagerInterface::class));

        (new TimestampableListener())->prePersist($event);

        self::assertInstanceOf(\DateTimeImmutable::class, $product->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $product->getUpdatedAt());
    }

    public function testPrePersistIgnoresNonTimestampableEntities(): void
    {
        $event = new PrePersistEventArgs(new \stdClass(), $this->createStub(EntityManagerInterface::class));

        (new TimestampableListener())->prePersist($event);

        $this->expectNotToPerformAssertions();
    }

    public function testPreUpdateSetsUpdatedAtAndRecomputesChangeSet(): void
    {
        $product = new Product();

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::once())->method('recomputeSingleEntityChangeSet');

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $entityManager->method('getClassMetadata')->willReturn($this->createStub(ClassMetadata::class));

        $changeSet = [];
        $event = new PreUpdateEventArgs($product, $entityManager, $changeSet);

        (new TimestampableListener())->preUpdate($event);

        self::assertInstanceOf(\DateTimeImmutable::class, $product->getUpdatedAt());
    }
}
