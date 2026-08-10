<?php

namespace App\Tests\Functional\Api;

use App\Tests\Functional\AbstractApiTestCase;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class CategoryApiTest extends AbstractApiTestCase
{
    public function testCreateReturnsCleanJsonWithAllFields(): void
    {
        $response = $this->postCategory(static::createClient(), 'ELEC');

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        self::assertSame('ELEC', $data['code']);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('createdAt', $data);
        self::assertArrayHasKey('updatedAt', $data);
        self::assertArrayNotHasKey('@context', $data);
    }

    public function testDuplicateCodeIsRejected(): void
    {
        $client = static::createClient();
        $this->postCategory($client, 'ELEC');
        $this->postCategory($client, 'ELEC');

        self::assertResponseStatusCodeSame(422);
    }

    public function testCodeLongerThan10CharsIsRejected(): void
    {
        $this->postCategory(static::createClient(), 'ABCDEFGHIJK'); // 11 chars

        self::assertResponseStatusCodeSame(422);
    }

    public function testCodeWithExactly10CharsIsAccepted(): void
    {
        $response = $this->postCategory(static::createClient(), 'ABCDEFGHIJ'); // 10 chars

        self::assertResponseStatusCodeSame(201);
        self::assertSame('ABCDEFGHIJ', $response->toArray()['code']);
    }

    public function testLowercaseCodeIsNowAllowed(): void
    {
        $response = $this->postCategory(static::createClient(), 'elec');

        self::assertResponseStatusCodeSame(201);
        self::assertSame('elec', $response->toArray()['code']);
    }

    public function testCodeWithInvalidCharactersIsRejected(): void
    {
        $this->postCategory(static::createClient(), 'bad code'); // space is not allowed

        self::assertResponseStatusCodeSame(422);
    }

    public function testBlankCodeIsRejected(): void
    {
        $this->postCategory(static::createClient(), '');

        self::assertResponseStatusCodeSame(422);
    }

    public function testGetUnknownCategoryReturns404(): void
    {
        static::createClient()->request('GET', '/api/categories/999999', ['headers' => ['accept' => 'application/json']]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testUpdateCodeViaPatch(): void
    {
        $client = static::createClient();
        $id = $this->postCategory($client, 'ELEC')->toArray()['id'];

        $response = $client->request('PATCH', '/api/categories/'.$id, [
            'headers' => ['accept' => 'application/json', 'content-type' => 'application/merge-patch+json'],
            'body' => json_encode(['code' => 'ELEX']),
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertSame('ELEX', $response->toArray()['code']);
    }

    public function testPatchToAnAlreadyUsedCodeIsRejected(): void
    {
        $client = static::createClient();
        $this->postCategory($client, 'ELEC');
        $homeId = $this->postCategory($client, 'HOME')->toArray()['id'];

        $client->request('PATCH', '/api/categories/'.$homeId, [
            'headers' => ['accept' => 'application/json', 'content-type' => 'application/merge-patch+json'],
            'body' => json_encode(['code' => 'ELEC']),
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testDeleteCategory(): void
    {
        $client = static::createClient();
        $id = $this->postCategory($client, 'ELEC')->toArray()['id'];

        $client->request('DELETE', '/api/categories/'.$id, ['headers' => ['accept' => 'application/json']]);
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/categories/'.$id, ['headers' => ['accept' => 'application/json']]);
        self::assertResponseStatusCodeSame(404);
    }

    private function postCategory(object $client, string $code): ResponseInterface
    {
        return $client->request('POST', '/api/categories', [
            'json' => ['code' => $code],
            'headers' => ['accept' => 'application/json'],
        ]);
    }
}
