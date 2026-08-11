<?php

namespace App\DataFixtures;

use App\Domain\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [
            ['email' => 'admin@example.com', 'password' => 'admin1234', 'roles' => ['ROLE_ADMIN']],
            ['email' => 'user@example.com', 'password' => 'user1234', 'roles' => ['ROLE_USER']],
        ];

        foreach ($users as $data) {
            $user = (new User())
                ->setEmail($data['email'])
                ->setRoles($data['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

            $manager->persist($user);
        }

        $manager->flush();
    }
}
