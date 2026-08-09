<?php

namespace App\Domain\Shared\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait HavingAutoIncrementedIdEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
