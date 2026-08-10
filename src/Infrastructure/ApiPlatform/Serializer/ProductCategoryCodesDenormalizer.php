<?php

namespace App\Infrastructure\ApiPlatform\Serializer;

use App\Domain\Category\Entity\Category;
use App\Domain\Product\Entity\Product;
use App\Infrastructure\Doctrine\Repository\CategoryRepository;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ProductCategoryCodesDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = 'PRODUCT_CATEGORY_CODES_DENORMALIZER_ALREADY_CALLED';

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Product::class === $type
            && \is_array($data)
            && !($context[self::ALREADY_CALLED] ?? false);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var Product $product */
        $product = $this->denormalizer->denormalize($data, $type, $format, $context);

        if (\is_array($data) && \array_key_exists('categoryCodes', $data)) {
            $this->applyCategoryCodes($product);
        }

        return $product;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Product::class => false];
    }

    private function applyCategoryCodes(Product $product): void
    {
        // Provided codes define full category set.
        foreach ($product->getCategories()->toArray() as $existing) {
            $product->removeCategory($existing);
        }

        $seen = [];
        foreach ($product->getCategoryCodes() as $code) {
            if (!\is_string($code)) {
                continue;
            }

            $code = trim($code);
            if ('' === $code || isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;

            $category = $this->categoryRepository->findOneBy(['code' => $code]) ?? (new Category())->setCode($code);

            $product->addCategory($category);
        }
    }
}
