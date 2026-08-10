<?php

namespace App\Tests\Unit\Domain\Category\Entity;

use App\Domain\Category\Entity\Category;
use App\Domain\Product\Entity\Product;
use PHPUnit\Framework\TestCase;

final class CategoryTest extends TestCase
{
    public function testAddProductSyncsInverseSideAndIsIdempotent(): void
    {
        $category = new Category();
        $product = new Product();

        $category->addProduct($product);
        $category->addProduct($product);

        self::assertCount(1, $category->getProducts());
        self::assertTrue($product->getCategories()->contains($category));
    }

    public function testRemoveProductSyncsInverseSide(): void
    {
        $category = new Category();
        $product = new Product();
        $category->addProduct($product);

        $category->removeProduct($product);

        self::assertCount(0, $category->getProducts());
        self::assertFalse($product->getCategories()->contains($category));
    }
}
