<?php

declare(strict_types=1);

namespace LocalMcp\Tests\Auth;

use LocalMcp\Auth\ApiKeyAuthenticator;
use PHPUnit\Framework\TestCase;

final class ApiKeyAuthenticatorTest extends TestCase
{
    public function testAuthenticatesValidBearerToken(): void
    {
        $auth = ApiKeyAuthenticator::fromKeys(['secret-one', 'secret-two']);

        self::assertTrue($auth->authenticate('Bearer secret-one'));
        self::assertTrue($auth->authenticate('Bearer secret-two'));
    }

    public function testRejectsInvalidToken(): void
    {
        $auth = ApiKeyAuthenticator::fromKeys(['secret-one']);

        self::assertFalse($auth->authenticate('Bearer wrong'));
        self::assertFalse($auth->authenticate('Bearer '));
        self::assertFalse($auth->authenticate(null));
        self::assertFalse($auth->authenticate('Basic secret-one'));
        self::assertFalse($auth->authenticate('secret-one'));
    }

    public function testRejectsWhenNoKeysConfigured(): void
    {
        $auth = ApiKeyAuthenticator::fromKeys([]);

        self::assertFalse($auth->authenticate('Bearer anything'));
    }

    public function testIsCaseInsensitiveOnBearerPrefix(): void
    {
        $auth = ApiKeyAuthenticator::fromKeys(['abc']);

        self::assertTrue($auth->authenticate('bearer abc'));
        self::assertTrue($auth->authenticate('BEARER abc'));
    }
}
