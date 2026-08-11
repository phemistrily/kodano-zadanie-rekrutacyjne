<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Base class for functional API tests.
 */
abstract class AbstractApiTestCase extends ApiTestCase
{
    use MailerAssertionsTrait;

    // Boot the kernel when a client is created (current behaviour, explicit for API Platform 5 forward-compat).
    protected static ?bool $alwaysBootKernel = true;

    protected const TEST_EMAIL = 'test@example.com';
    protected const TEST_PASSWORD = 'test1234';

    private string $token = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareDatabaseAndToken();
    }

    protected function authClient(): Client
    {
        return static::createClient([], ['auth_bearer' => $this->token]);
    }

    private function prepareDatabaseAndToken(): void
    {
        $container = self::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();

        $tables = ['product_category', 'product', 'category', 'user'];

        if (!in_array('user', $connection->createSchemaManager()->listTableNames(), true)) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            foreach ($tables as $table) {
                $connection->executeStatement(sprintf('DROP TABLE IF EXISTS %s', $table));
            }
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

            $schemaTool = new SchemaTool($entityManager);
            $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $connection->executeStatement(sprintf('TRUNCATE TABLE %s', $table));
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        $user = (new User())->setEmail(self::TEST_EMAIL)->setRoles(['ROLE_USER']);
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, self::TEST_PASSWORD));
        $entityManager->persist($user);
        $entityManager->flush();

        $this->token = $container->get('lexik_jwt_authentication.jwt_manager')->create($user);

        self::ensureKernelShutdown();
    }
}
