<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

/**
 * Base class for functional API tests.
 */
abstract class AbstractApiTestCase extends ApiTestCase
{
    use MailerAssertionsTrait;

    protected static ?bool $alwaysBootKernel = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSchema();
    }

    private function resetSchema(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();

        if (!in_array('product', $connection->createSchemaManager()->listTableNames(), true)) {
            $schemaTool = new SchemaTool($entityManager);
            $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['product_category', 'product', 'category'] as $table) {
            $connection->executeStatement(sprintf('TRUNCATE TABLE %s', $table));
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        self::ensureKernelShutdown();
    }
}
