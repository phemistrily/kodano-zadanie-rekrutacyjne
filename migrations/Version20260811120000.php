<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Demo credentials: admin@example.com / admin1234 (the password hash below is a
 * bcrypt hash of "admin1234"). Create more users with `php bin/console app:create-user`.
 */
final class Version20260811120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql(
            'INSERT INTO user (email, roles, password) VALUES (:email, :roles, :password)',
            [
                'email' => 'admin@example.com',
                'roles' => '["ROLE_USER"]',
                'password' => '$2y$12$CgeuqgwB3MEf6G0FuHFLXOylyPZTEVL7W0Y/YzSQkHuru8jyfygM.',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user');
    }
}
