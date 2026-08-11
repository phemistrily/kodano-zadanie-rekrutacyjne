<?php

namespace App\DataFixtures;

use App\Domain\Category\Entity\Category;
use App\Domain\Product\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $products = [
            ['name' => 'Laptop Pro 15', 'price' => '4999.00', 'categories' => ['ELEC']],
            ['name' => 'Smartfon X', 'price' => '2599.99', 'categories' => ['ELEC']],
            ['name' => 'Ekspres do kawy', 'price' => '899.00', 'categories' => ['HOME', 'ELEC']],
            ['name' => 'Zestaw garnkow', 'price' => '349.50', 'categories' => ['HOME']],
            ['name' => 'Powiesc Wydma', 'price' => '39.99', 'categories' => ['BOOKS']],
            ['name' => 'Klocki konstrukcyjne', 'price' => '149.90', 'categories' => ['TOYS']],
            ['name' => 'Mis pluszowy', 'price' => '59.00', 'categories' => ['TOYS']],
            ['name' => 'Kawa ziarnista 1kg', 'price' => '69.99', 'categories' => ['FOOD', 'HOME']],
        ];

        foreach ($products as $data) {
            $product = (new Product())
                ->setName($data['name'])
                ->setPrice($data['price']);

            foreach ($data['categories'] as $code) {
                /** @var Category $category */
                $category = $this->getReference(CategoryFixtures::reference($code), Category::class);
                $product->addCategory($category);
            }

            $manager->persist($product);
        }

        $manager->flush();
    }

    /**
     * @return array<int, class-string>
     */
    public function getDependencies(): array
    {
        return [CategoryFixtures::class];
    }
}
