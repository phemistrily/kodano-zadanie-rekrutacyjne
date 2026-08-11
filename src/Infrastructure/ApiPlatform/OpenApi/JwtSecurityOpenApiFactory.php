<?php

namespace App\Infrastructure\ApiPlatform\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;

final class JwtSecurityOpenApiFactory implements OpenApiFactoryInterface
{
    private const PUBLIC_PATHS = ['/api/login_check'];

    /** @var array<int, array<string, array<int, string>>> */
    private const REQUIREMENT = [['JWT' => []]];

    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = $openApi->getPaths();

        foreach ($paths->getPaths() as $path => $pathItem) {
            if (in_array($path, self::PUBLIC_PATHS, true)) {
                continue;
            }
            $paths->addPath($path, $this->secure($pathItem));
        }

        return $openApi;
    }

    private function secure(PathItem $pathItem): PathItem
    {
        foreach (['Get', 'Post', 'Put', 'Patch', 'Delete'] as $method) {
            $operation = $pathItem->{'get'.$method}();
            if ($operation instanceof Operation) {
                $pathItem = $pathItem->{'with'.$method}($operation->withSecurity(self::REQUIREMENT));
            }
        }

        return $pathItem;
    }
}
