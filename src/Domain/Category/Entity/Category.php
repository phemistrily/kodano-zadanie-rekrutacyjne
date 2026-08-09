<?php

namespace App\Domain\Category\Entity;

use App\Domain\Product\Entity\Product;
use App\Domain\Shared\Entity\Traits\HavingAutoIncrementedIdEntity;
use App\Domain\Shared\Entity\Traits\TimestampableEntity;
use App\Domain\Shared\Entity\Traits\TimestampableInterface;
use App\Infrastructure\Doctrine\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'category')]
#[UniqueEntity(
    fields: ['code'],
    message: 'Kategoria o kodzie "{{ value }}" już istnieje.',
)]
class Category implements TimestampableInterface
{
    use HavingAutoIncrementedIdEntity;
    use TimestampableEntity;
    #[ORM\Column(length: 10, unique: true)]
    #[Assert\NotBlank(message: 'Kod kategorii jest wymagany.')]
    #[Assert\Length(max: 10, maxMessage: 'Kod może mieć maksymalnie {{ limit }} znaków.')]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9_-]+$/',
        message: 'Kod może zawierać tylko wielkie litery, cyfry, podkreślenie i myślnik.',
    )]
    private ?string $code = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'category')]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->addCategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            $product->removeCategory($this);
        }

        return $this;
    }
}
