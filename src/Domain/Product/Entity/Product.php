<?php

namespace App\Domain\Product\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Domain\Category\Entity\Category;
use App\Domain\Shared\Entity\Traits\HavingAutoIncrementedIdEntity;
use App\Domain\Shared\Entity\Traits\TimestampableEntity;
use App\Domain\Shared\Entity\Traits\TimestampableInterface;
use App\Infrastructure\Doctrine\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
#[ApiResource(
    normalizationContext: ['groups' => ['product:read', 'timestamps:read']],
    denormalizationContext: ['groups' => ['product:write']],
)]
class Product implements TimestampableInterface
{
    use HavingAutoIncrementedIdEntity;
    use TimestampableEntity;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull]
    #[Assert\Type(type: 'numeric', message: 'Cena musi być liczbą.')]
    #[Assert\PositiveOrZero]
    #[Assert\Regex(
        pattern: '/^\d+(\.\d{1,2})?$/',
        message: 'Cena może mieć maksymalnie dwa miejsca po przecinku.',
    )]
    #[Groups(['product:read', 'product:write'])]
    private ?string $price = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'products', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'product_category')]
    #[Assert\Count(
        min: 1,
        minMessage: 'Produkt musi należeć do co najmniej jednej kategorii.',
    )]
    #[Assert\Valid]
    #[Groups(['product:read'])]
    private Collection $categories;

    #[ApiProperty(
        readable: false,
        writable: true,
        schema: ['type' => 'array', 'items' => ['type' => 'string'], 'example' => ['ELEC']],
    )]
    #[Groups(['product:write'])]
    private array $categoryCodes = [];

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getCategoryCodes(): array
    {
        return $this->categoryCodes;
    }

    /**
     * @param list<string> $categoryCodes
     */
    public function setCategoryCodes(array $categoryCodes): static
    {
        $this->categoryCodes = $categoryCodes;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }
}
