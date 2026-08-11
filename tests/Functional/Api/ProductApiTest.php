<?php

namespace App\Tests\Functional\Api;

use App\Tests\Functional\AbstractApiTestCase;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ProductApiTest extends AbstractApiTestCase
{
    public function testCreateWithNewCategoryCodeAutoCreatesCategory(): void
    {
        $client = $this->authClient();
        $response = $this->postProduct($client, ['name' => 'Konsola', 'price' => '1299.00', 'categoryCodes' => ['GAMING']]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        self::assertSame('Konsola', $data['name']);
        self::assertSame('1299.00', $data['price']);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('createdAt', $data);
        self::assertArrayHasKey('updatedAt', $data);
        self::assertArrayNotHasKey('@context', $data);
        self::assertCount(1, $data['categories']);
        self::assertSame('GAMING', $data['categories'][0]['code']);

        self::assertSame(['GAMING'], $this->categoryCodes($client));
    }

    public function testCreateWithMultipleCategoryCodes(): void
    {
        $client = $this->authClient();
        $data = $this->postProduct($client, [
            'name' => 'Zestaw',
            'price' => '10.00',
            'categoryCodes' => ['ELEC', 'HOME', 'GAMING'],
        ])->toArray();

        self::assertResponseStatusCodeSame(201);
        self::assertCount(3, $data['categories']);
        self::assertCount(3, $this->categoryCodes($client));
    }

    public function testDuplicateCategoryCodesInOneRequestAreDeduplicated(): void
    {
        $client = $this->authClient();
        $data = $this->postProduct($client, ['name' => 'Produkt', 'price' => '10.00', 'categoryCodes' => ['ELEC', 'ELEC']])->toArray();

        self::assertResponseStatusCodeSame(201);
        self::assertCount(1, $data['categories']);
        self::assertCount(1, $this->categoryCodes($client));
    }

    public function testMixOfExistingAndNewCategoryCodes(): void
    {
        $client = $this->authClient();
        $client->request('POST', '/api/categories', ['json' => ['code' => 'ELEC'], 'headers' => ['accept' => 'application/json']]);

        $data = $this->postProduct($client, ['name' => 'Produkt', 'price' => '10.00', 'categoryCodes' => ['ELEC', 'NEW']])->toArray();

        self::assertResponseStatusCodeSame(201);
        self::assertCount(2, $data['categories']);
        self::assertCount(2, $this->categoryCodes($client));
    }

    public function testExistingCategoryIsReusedNotDuplicated(): void
    {
        $client = $this->authClient();
        $this->postProduct($client, ['name' => 'Produkt A', 'price' => '10.00', 'categoryCodes' => ['ELEC']]);
        self::assertResponseStatusCodeSame(201);
        $this->postProduct($client, ['name' => 'Produkt B', 'price' => '20.00', 'categoryCodes' => ['ELEC']]);
        self::assertResponseStatusCodeSame(201);

        self::assertCount(1, $this->categoryCodes($client));
    }

    public function testCategoriesFieldIsReadOnlyOnWrite(): void
    {
        $this->postProduct($this->authClient(), ['name' => 'Produkt X', 'price' => '10.00', 'categories' => ['/api/categories/1']]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testProductWithoutCategoriesIsRejected(): void
    {
        $this->postProduct($this->authClient(), ['name' => 'Produkt X', 'price' => '10.00', 'categoryCodes' => []]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testProductWithoutNameIsRejected(): void
    {
        $this->postProduct($this->authClient(), ['price' => '10.00', 'categoryCodes' => ['ELEC']]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testNonNumericPriceIsRejected(): void
    {
        $this->postProduct($this->authClient(), ['name' => 'Produkt X', 'price' => 'abc', 'categoryCodes' => ['ELEC']]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testNegativePriceIsRejected(): void
    {
        $this->postProduct($this->authClient(), ['name' => 'Produkt X', 'price' => '-5.00', 'categoryCodes' => ['ELEC']]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testInvalidCategoryCodeIsRejectedAndCreatesNoCategory(): void
    {
        $client = $this->authClient();
        $this->postProduct($client, ['name' => 'Produkt X', 'price' => '10.00', 'categoryCodes' => ['bad code']]);
        self::assertResponseStatusCodeSame(422);

        self::assertCount(0, $this->categoryCodes($client));
    }

    public function testGetUnknownProductReturns404(): void
    {
        $this->authClient()->request('GET', '/api/products/999999', ['headers' => ['accept' => 'application/json']]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testUpdatePriceViaPatch(): void
    {
        $client = $this->authClient();
        $id = $this->postProduct($client, ['name' => 'Produkt A', 'price' => '10.00', 'categoryCodes' => ['ELEC']])->toArray()['id'];

        $response = $client->request('PATCH', '/api/products/'.$id, [
            'headers' => ['accept' => 'application/json', 'content-type' => 'application/merge-patch+json'],
            'body' => json_encode(['price' => '99.99']),
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertSame('99.99', $response->toArray()['price']);
    }

    public function testPatchOnlyNameKeepsPriceAndCategories(): void
    {
        $client = $this->authClient();
        $id = $this->postProduct($client, ['name' => 'Stara nazwa', 'price' => '10.00', 'categoryCodes' => ['ELEC']])->toArray()['id'];

        $data = $client->request('PATCH', '/api/products/'.$id, [
            'headers' => ['accept' => 'application/json', 'content-type' => 'application/merge-patch+json'],
            'body' => json_encode(['name' => 'Nowa nazwa']),
        ])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame('Nowa nazwa', $data['name']);
        self::assertSame('10.00', $data['price']); // unchanged
        $codes = array_map(static fn (array $c): string => $c['code'], $data['categories']);
        self::assertSame(['ELEC'], $codes); // categories untouched when categoryCodes is omitted
    }

    public function testPatchCategoryCodesReplacesCategories(): void
    {
        $client = $this->authClient();
        $id = $this->postProduct($client, ['name' => 'Produkt A', 'price' => '10.00', 'categoryCodes' => ['AAA']])->toArray()['id'];

        $response = $client->request('PATCH', '/api/products/'.$id, [
            'headers' => ['accept' => 'application/json', 'content-type' => 'application/merge-patch+json'],
            'body' => json_encode(['categoryCodes' => ['BBB']]),
        ]);

        self::assertResponseStatusCodeSame(200);
        $codes = array_map(static fn (array $c): string => $c['code'], $response->toArray()['categories']);
        self::assertSame(['BBB'], $codes);
    }

    public function testDeleteProduct(): void
    {
        $client = $this->authClient();
        $id = $this->postProduct($client, ['name' => 'Produkt A', 'price' => '10.00', 'categoryCodes' => ['ELEC']])->toArray()['id'];

        $client->request('DELETE', '/api/products/'.$id, ['headers' => ['accept' => 'application/json']]);
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/products/'.$id, ['headers' => ['accept' => 'application/json']]);
        self::assertResponseStatusCodeSame(404);
    }

    public function testCreatingProductSendsNotificationEmail(): void
    {
        $this->postProduct($this->authClient(), ['name' => 'Laptop', 'price' => '10.00', 'categoryCodes' => ['ELEC']]);

        self::assertResponseStatusCodeSame(201);
        self::assertEmailCount(1);
        self::assertStringContainsString('Produkt zapisany', self::getMailerMessage()->getSubject());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postProduct(object $client, array $payload): ResponseInterface
    {
        return $client->request('POST', '/api/products', [
            'json' => $payload,
            'headers' => ['accept' => 'application/json'],
        ]);
    }

    /**
     * @return list<string>
     */
    private function categoryCodes(object $client): array
    {
        $categories = $client->request('GET', '/api/categories', ['headers' => ['accept' => 'application/json']])->toArray();

        return array_map(static fn (array $c): string => $c['code'], $categories);
    }
}
