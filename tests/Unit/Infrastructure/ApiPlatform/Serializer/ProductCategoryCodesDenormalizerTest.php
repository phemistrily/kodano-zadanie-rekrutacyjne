<?php

namespace App\Tests\Unit\Infrastructure\ApiPlatform\Serializer;

use App\Domain\Category\Entity\Category;
use App\Domain\Product\Entity\Product;
use App\Infrastructure\ApiPlatform\Serializer\ProductCategoryCodesDenormalizer;
use App\Infrastructure\Doctrine\Repository\CategoryRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ProductCategoryCodesDenormalizerTest extends TestCase
{
    private const ALREADY_CALLED = 'PRODUCT_CATEGORY_CODES_DENORMALIZER_ALREADY_CALLED';

    public function testSupportsOnlyProductAndOnlyOnce(): void
    {
        $denormalizer = new ProductCategoryCodesDenormalizer($this->createStub(CategoryRepository::class));

        self::assertTrue($denormalizer->supportsDenormalization([], Product::class));
        self::assertFalse($denormalizer->supportsDenormalization([], Category::class));
        self::assertFalse($denormalizer->supportsDenormalization([], Product::class, null, [self::ALREADY_CALLED => true]));
    }

    public function testResolvesExistingCategoryByCode(): void
    {
        $existing = (new Category())->setCode('ELEC');

        $repository = $this->createStub(CategoryRepository::class);
        $repository->method('findOneBy')->willReturn($existing);

        $product = $this->denormalize($repository, ['ELEC'], ['categoryCodes' => ['ELEC']]);

        self::assertCount(1, $product->getCategories());
        self::assertSame($existing, $product->getCategories()->first());
    }

    public function testCreatesCategoryForUnknownCode(): void
    {
        $repository = $this->createStub(CategoryRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $product = $this->denormalize($repository, ['NEW'], ['categoryCodes' => ['NEW']]);

        self::assertCount(1, $product->getCategories());
        self::assertSame('NEW', $product->getCategories()->first()->getCode());
    }

    public function testDeduplicatesTrimsAndSkipsBlankCodes(): void
    {
        $repository = $this->createStub(CategoryRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $product = $this->denormalize($repository, ['ELEC', ' ELEC ', ''], ['categoryCodes' => ['ELEC', ' ELEC ', '']]);

        self::assertCount(1, $product->getCategories());
        self::assertSame('ELEC', $product->getCategories()->first()->getCode());
    }

    public function testReplacesPreviouslyLinkedCategories(): void
    {
        $repository = $this->createStub(CategoryRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $product = (new Product())->setName('Laptop')->setPrice('10.00');
        $product->addCategory((new Category())->setCode('OLD'));
        $product->setCategoryCodes(['NEW']);

        $inner = $this->createStub(DenormalizerInterface::class);
        $inner->method('denormalize')->willReturn($product);

        $denormalizer = new ProductCategoryCodesDenormalizer($repository);
        $denormalizer->setDenormalizer($inner);

        $result = $denormalizer->denormalize(['categoryCodes' => ['NEW']], Product::class);

        $codes = array_map(static fn (Category $c): ?string => $c->getCode(), $result->getCategories()->toArray());
        self::assertSame(['NEW'], array_values($codes));
    }

    public function testLeavesCategoriesUntouchedWhenCodesKeyIsAbsent(): void
    {
        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects(self::never())->method('findOneBy');

        $product = $this->denormalize($repository, [], ['name' => 'Laptop']);

        self::assertCount(0, $product->getCategories());
    }

    public function testSkipsNonStringCodes(): void
    {
        $repository = $this->createStub(CategoryRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $product = $this->denormalize($repository, [123, 'ELEC'], ['categoryCodes' => [123, 'ELEC']]);

        self::assertCount(1, $product->getCategories());
        self::assertSame('ELEC', $product->getCategories()->first()->getCode());
    }

    private function denormalize(CategoryRepository $repository, array $codesOnProduct, array $data): Product
    {
        $product = (new Product())->setName('Laptop')->setPrice('10.00');
        $product->setCategoryCodes($codesOnProduct);

        $inner = $this->createStub(DenormalizerInterface::class);
        $inner->method('denormalize')->willReturn($product);

        $denormalizer = new ProductCategoryCodesDenormalizer($repository);
        $denormalizer->setDenormalizer($inner);

        return $denormalizer->denormalize($data, Product::class);
    }
}
