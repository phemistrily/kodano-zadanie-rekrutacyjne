<?php

namespace App\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Category\Entity\Category;
use App\Domain\Notification\NotifierInterface;
use App\Domain\Notification\Notification;
use App\Domain\Product\Entity\Product;

final class ProductNotificationProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed, mixed> $inner
     */
    public function __construct(
        private readonly ProcessorInterface $inner,
        private readonly NotifierInterface $notifier,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $result = $this->inner->process($data, $operation, $uriVariables, $context);

        if ($result instanceof Product) {
            $this->notifier->notify($this->buildNotification($result));
        }

        return $result;
    }

    private function buildNotification(Product $product): Notification
    {
        $categoryCodes = array_values(array_filter(array_map(
            static fn (Category $category): ?string => $category->getCode(),
            $product->getCategories()->toArray(),
        )));

        $subject = sprintf('Produkt zapisany: %s', (string) $product->getName());

        $message = sprintf(
            "Produkt \"%s\" (ID: %s) został zapisany.\nCena: %s\nKategorie: %s",
            (string) $product->getName(),
            (string) $product->getId(),
            (string) $product->getPrice(),
            $categoryCodes === [] ? '-' : implode(', ', $categoryCodes),
        );

        return new Notification($subject, $message, [
            'productId' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'categories' => $categoryCodes,
        ]);
    }
}
