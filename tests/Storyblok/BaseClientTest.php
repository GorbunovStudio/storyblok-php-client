<?php

declare(strict_types=1);

namespace Storyblok\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Storyblok\BaseClient;
use Storyblok\Client;

/**
 * @internal
 */
#[CoversClass(BaseClient::class)]
final class BaseClientTest extends TestCase
{
    public function testSetApiKey(): void
    {
        $client = new BaseClient('test');
        self::assertSame('test', $client->getApiKey());
        $client->setApiKey('new-test');
        self::assertSame('new-test', $client->getApiKey());
    }

    public function testSetProxy(): void
    {
        $client = new BaseClient('test');
        $client->setProxy('http://127.0.0.1');

        self::assertSame('http://127.0.0.1', $client->getProxy());
    }

    public function testSetTimeout(): void
    {
        $client = new BaseClient('test');
        $client->setTimeout(20);

        self::assertSame(20, $client->getTimeout());
    }

    public function testSetMaxRetries(): void
    {
        $client = new BaseClient('test');
        $client->setMaxRetries(3);

        self::assertSame(3, $client->getMaxRetries());
    }

    public function testGetHeaders(): void
    {
        $client = new Client('test', $endpoint = 'stories', $version = 'v1');
        $client->mockable([
            mockResponse($endpoint, ['server' => 'nginx/1.18.0'], $version),
        ]);

        $headers = $client->getStories()->getHeaders();

        self::assertSame('nginx/1.18.0', $headers['server'][0]);
    }

    public function testGetBody(): void
    {
        $client = new Client('test', $endpoint = 'stories', $version = 'v1');
        $client->mockable([
            mockResponse($endpoint, [], $version),
        ]);

        $body = $client->getStories()->getBody();

        self::assertArrayHasKey('stories', $body);
    }

    public function testGetStatusCode(): void
    {
        $client = new Client('test', $endpoint = 'stories', $version = 'v1');
        $client->mockable([
            mockResponse($endpoint, [], $version, 202),
        ]);

        $statusCode = $client->getStories()->getCode();

        self::assertSame(202, $statusCode);
    }
}
