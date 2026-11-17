<?php

declare(strict_types=1);

namespace Storyblok\Tests;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Storyblok\ApiException;
use Storyblok\ManagementClient;

/**
 * @internal
 */
#[CoversClass(ManagementClient::class)]
final class ManagementClientTest extends TestCase
{
    public function testCanBeInstanced(): void
    {
        self::assertInstanceOf(ManagementClient::class, new ManagementClient('token'));
    }

    public function testResponseHasBody(): void
    {
        $client = new ManagementClient('token');
        $client->mockable([
            new Response(200, [], '{"foo":"bar"}'),
        ]);

        $response = $client->post('test', []);

        self::assertSame(['foo' => 'bar'], $response->getBody());
    }

    public function testResponseHasHeader(): void
    {
        $client = new ManagementClient('token');
        $client->mockable([
            new Response(200, ['foo' => 'bar'], ''),
        ]);

        $response = $client->post('test', []);

        self::assertSame(['foo' => ['bar']], $response->getHeaders());
    }

    public function testResponseHasStatusCode(): void
    {
        $client = new ManagementClient('token');
        $client->mockable([
            new Response(200, [], ''),
        ]);

        $response = $client->post('test', []);

        self::assertSame(200, $response->getCode());
    }

    public function testResponseHasProxy(): void
    {
        $client = new ManagementClient('token');
        $client->setProxy('127.0.0.1');
        $client->mockable([
            new Response(201, [], ''),
            new Response(200, [], ''),
            new Response(200, [], ''),
        ]);

        $post = $client->post('test', []);
        $put = $client->put('test', []);
        $delete = $client->delete('test', []);

        self::assertSame('127.0.0.1', $client->getProxy());
        self::assertSame(201, $post->httpResponseCode);
        self::assertSame(200, $put->httpResponseCode);
    }

    public function testPostRequestThrowsExceptionOnError(): void
    {
        $client = new ManagementClient('token');
        $client->mockable([
            new ClientException('Error', new Request('POST', 'test'), new Response()),
        ]);

        $this->expectException(ApiException::class);

        $client->post('test', []);
    }

    public function testPutRequestThrowsExceptionOnError(): void
    {
        $client = new ManagementClient('token');
        $client->mockable([
            new ClientException('Error', new Request('PUT', 'test'), new Response()),
        ]);

        $this->expectException(ApiException::class);

        $client->put('test', []);
    }

    public function testDeleteRequestThrowsExceptionOnError(): void
    {
        $client = new ManagementClient('token');
        $client->mockable([
            new ClientException('Error', new Request('DELETE', 'test'), new Response()),
        ]);

        $this->expectException(ApiException::class);

        $client->delete('test', []);
    }

    public function testGetWithDefaultPayload(): void
    {
        $client = new ManagementClient('token');
        $client->mockable([new Response(200, [], '{"stories":[]}')]);

        self::assertSame(['stories' => []], $client->get('spaces/1/stories')->getBody());
    }

    public function testGetWithQueryPayload(): void
    {
        $client = new ManagementClient('token');
        $client->mockable([new Response(200, [], '{"stories":[]}')]);

        self::assertSame(['stories' => []], $client->get('spaces/1/stories', ['page' => 1])->getBody());
    }
}
