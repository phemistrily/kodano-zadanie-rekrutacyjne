<?php

namespace App\Tests\Functional\Api;

use App\Tests\Functional\AbstractApiTestCase;

final class AuthTest extends AbstractApiTestCase
{
    public function testUnauthenticatedRequestIsRejected(): void
    {
        static::createClient()->request('GET', '/api/products', ['headers' => ['accept' => 'application/json']]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginReturnsAToken(): void
    {
        $response = static::createClient()->request('POST', '/api/login_check', [
            'json' => ['email' => self::TEST_EMAIL, 'password' => self::TEST_PASSWORD],
            'headers' => ['accept' => 'application/json'],
        ]);

        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('token', $response->toArray());
    }

    public function testLoginWithInvalidCredentialsIsRejected(): void
    {
        static::createClient()->request('POST', '/api/login_check', [
            'json' => ['email' => self::TEST_EMAIL, 'password' => 'wrong-password'],
            'headers' => ['accept' => 'application/json'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testApiDocsArePubliclyAccessible(): void
    {
        static::createClient()->request('GET', '/api/docs.jsonld', ['headers' => ['accept' => 'application/ld+json']]);

        self::assertResponseIsSuccessful();
    }

    public function testAuthenticatedRequestSucceeds(): void
    {
        $this->authClient()->request('GET', '/api/products', ['headers' => ['accept' => 'application/json']]);

        self::assertResponseIsSuccessful();
    }
}
