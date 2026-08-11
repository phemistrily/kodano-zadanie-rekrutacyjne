<?php

namespace App\DataFixtures;

use App\Domain\Category\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class CategoryFixtures extends Fixture
{
    /**
     * @var list<string>
     */
    public const CODES = ['ELEC', 'HOME', 'BOOKS', 'TOYS', 'FOOD'];

    /**
     * Reference name used by ProductFixtures to link products to categories.
     */
    public static function reference(string $code): string
    {
        return 'category_'.$code;
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::CODES as $code) {
            $category = (new Category())->setCode($code);
            $manager->persist($category);
            $this->addReference(self::reference($code), $category);
        }

        $manager->flush();
    }
}
