<?php

namespace App\Tests\Unit\Domain\Product\Entity;

use App\Domain\Category\Entity\Category;
use App\Domain\Product\Entity\Product;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testAddCategoryIsIdempotent(): void
    {
        $product = new Product();
        $category = (new Category())->setCode('ELEC');

        $product->addCategory($category);
        $product->addCategory($category);

        self::assertCount(1, $product->getCategories());
        self::assertTrue($product->getCategories()->contains($category));
    }

    public function testRemoveCategory(): void
    {
        $product = new Product();
        $category = (new Category())->setCode('ELEC');
        $product->addCategory($category);

        $product->removeCategory($category);

        self::assertCount(0, $product->getCategories());
    }
}
