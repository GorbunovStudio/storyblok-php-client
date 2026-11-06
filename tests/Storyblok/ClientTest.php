<?php

declare(strict_types=1);

namespace Storyblok\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Storyblok\ApiException;
use Storyblok\Client;

/**
 * @internal
 */
#[CoversClass(Client::class)]
final class ClientTest extends TestCase
{
    public function testCanBeInstanced(): void
    {
        self::assertInstanceOf(Client::class, new Client('token'));
    }

    public function testRetrieveApiParameters(): void
    {
        $client = (new Client('test'))->getApiParameters();

        self::assertSame('test', $client['token']);
        self::assertSame('published', $client['version']);
        self::assertNull($client['cv']);
    }

    public function testStoryLanguageCanBeSet(): void
    {
        $client = new Client('test');
        $client->language('en');

        self::assertSame('en', $client->getLanguage());
    }

    public function testFallbackLanguageCanBeSet(): void
    {
        $client = new Client('test');
        $client->fallbackLanguage('en');

        self::assertSame('en', $client->getFallbackLanguage());
    }

    public function testDefaultContentVersionIsSetToOnlyPublishedStories(): void
    {
        $client = new Client('test');

        self::assertSame('published', $client->getVersion());
    }

    public function testSetContentToReceiveDraftVersions(): void
    {
        $client = new Client('test');
        $client->editMode(true);

        self::assertSame('draft', $client->getVersion());
    }

    public function testV1GetStories(): void
    {
        $client = new Client('test', $endpoint = 'stories', $version = 'v1');
        $client->mockable([
            mockResponse($endpoint, [], $version),
        ]);

        $stories = $client->getStories()->getBody();

        self::assertCount(13, $stories['stories']);
        self::assertArrayNotHasKey('cv', $stories);
        self::assertArrayNotHasKey('rels', $stories);
        self::assertArrayNotHasKey('links', $stories);
    }

    public function testV1GetStoryByUuid(): void
    {
        $client = new Client('test', $endpoint = 'storyByUuid', $version = 'v1');
        $client->mockable([
            mockResponse($endpoint, [], $version),
        ]);

        $story = $client->getStoryByUuid('d637be7f-8187-4e8b-9434-93390541f42b')->getBody();

        self::assertSame('d637be7f-8187-4e8b-9434-93390541f42b', $story['story']['uuid']);
        self::assertArrayNotHasKey('cv', $story);
        self::assertArrayNotHasKey('rels', $story);
        self::assertArrayNotHasKey('links', $story);
    }

    public function testV2GetStories(): void
    {
        $client = new Client('test', $endpoint = 'stories', $version = 'v2');
        $client->mockable([
            mockResponse($endpoint, [], $version),
        ]);

        $stories = $client->getStories()->getBody();

        self::assertCount(13, $stories['stories']);
        self::assertSame('Overview', $stories['stories'][0]['name']);
        self::assertArrayHasKey('cv', $stories);
        self::assertArrayHasKey('rels', $stories);
        self::assertArrayHasKey('links', $stories);
    }

    public function testV2GetStoriesWithCache(): void
    {
        $client = new Client('test', null, $version = 'v2');
        $client->setCache('filesystem', ['path' => './cache', 'default_lifetime' => 3600]);
        $client->cacheClear();
        $client->editMode(false);
        $client->mockable([
            mockResponse('stories', ['x-test' => '1'], $version),
            mockResponse('stories2', ['x-test' => '2'], $version),
        ]);

        $stories = $client->getStories()->getBody();
        self::assertCount(13, $stories['stories']);
        self::assertSame('Overview', $stories['stories'][0]['name']);
        self::assertArrayHasKey('cv', $stories);
        self::assertArrayHasKey('rels', $stories);
        self::assertArrayHasKey('links', $stories);

        $stories = $client->getStories()->getBody();
        self::assertCount(13, $stories['stories']);
        self::assertSame('Overview', $stories['stories'][0]['name']);
        self::assertArrayHasKey('cv', $stories);
        self::assertArrayHasKey('rels', $stories);
        self::assertArrayHasKey('links', $stories);
    }

    public function testV2GetStoryByUuid(): void
    {
        $client = new Client('test', $endpoint = 'storyByUuid', $version = 'v2');
        $client->mockable([
            mockResponse($endpoint, [], $version),
        ]);

        $story = $client->getStoryByUuid('d637be7f-8187-4e8b-9434-93390541f42b')->getBody();

        self::assertSame('d637be7f-8187-4e8b-9434-93390541f42b', $story['story']['uuid']);
        self::assertArrayHasKey('cv', $story);
        self::assertArrayHasKey('rels', $story);
        self::assertArrayHasKey('links', $story);
    }

    public function testV2GetStoryByUuidWithCache(): void
    {
        $client = new Client('test', $endpoint = 'storyByUuid', $version = 'v2');
        $client->setCache('filesystem', ['path' => 'cache']);
        $client->mockable([
            mockResponse($endpoint, [], $version),
        ]);

        $story = $client->getStoryByUuid('d637be7f-8187-4e8b-9434-93390541f42b')->getBody();

        self::assertSame('d637be7f-8187-4e8b-9434-93390541f42b', $story['story']['uuid']);
        self::assertArrayHasKey('cv', $story);
        self::assertArrayHasKey('rels', $story);
        self::assertArrayHasKey('links', $story);
    }

    public function testV2GetStoriesWithRelations(): void
    {
        $client = new Client('test', null, $version = 'v2');
        // $client->editMode(false);
        $client->mockable([
            mockResponse('stories-relations', ['x-test' => '1'], $version),
        ]);
        $client->resolveRelations('popular-articles.articles');
        $story = $client->getStoryBySlug('home')->getBody();
        self::assertArrayHasKey('story', $story);
        self::assertSame('Home', $story['story']['name']);
        self::assertCount(
            3,
            $story['story']['content']['body'][1]['articles']
        );
        self::assertSame(
            'Article 1',
            $story['story']['content']['body'][1]['articles'][0]['name']
        );
        self::assertSame(
            'Article 4',
            $story['story']['content']['body'][1]['articles'][1]['name']
        );
        self::assertSame(
            'Article 2',
            $story['story']['content']['body'][1]['articles'][2]['name']
        );
    }

    public function testV2CheckSortingForRelations(): void
    {
        $client = new Client('test', null, $version = 'v2');
        // $client->editMode(false);
        $client->mockable([
            mockResponse('stories-relations', ['x-test' => '1'], $version),
        ]);
        $client->resolveRelations('popular-articles.articles');
        $story = $client->getStoryBySlug('home')->getBody();
        self::assertArrayHasKey('story', $story);
        self::assertArrayHasKey('rels', $story);
        // With the dataset provided the right order should be
        // ["997cfee2-cec3-4cad-9e06-7ef3d72f5b7d", "ba307a05-2b91-4ce8-ab24-bf328da1adb4", "c3a321a5-1163-4281-8643-2c79c13e22a3"]

        self::assertSame(
            '997cfee2-cec3-4cad-9e06-7ef3d72f5b7d',
            $story['story']['content']['body'][1]['articles'][0]['uuid'],
            'checking the first relation'
        );
        self::assertSame(
            'ba307a05-2b91-4ce8-ab24-bf328da1adb4',
            $story['story']['content']['body'][1]['articles'][1]['uuid'],
            'checking the second relation'
        );
        self::assertSame(
            'c3a321a5-1163-4281-8643-2c79c13e22a3',
            $story['story']['content']['body'][1]['articles'][2]['uuid'],
            'checking the third relation'
        );
    }

    public function testV1GetTags(): void
    {
        $client = new Client('test', $endpoint = 'tags', $version = 'v1');
        $client->mockable([
            mockResponse($endpoint, [], $version),
        ]);

        $tags = $client->getTags()->getBody();
        self::assertArrayHasKey('tags', $tags);
        self::assertCount(2, $tags['tags']);
        self::assertArrayHasKey('name', $tags['tags'][0]);
        self::assertSame('red', $tags['tags'][0]['name']);
        self::assertArrayHasKey('taggings_count', $tags['tags'][0]);
        self::assertSame(14, $tags['tags'][0]['taggings_count']);
    }

    public function testV1GetTagsWith401Error(): void
    {
        $client = new Client('test', $endpoint = 'tags', $version = 'v1');
        $client->mockable([
            mockResponse($endpoint, [], $version, 401),
            mockResponse($endpoint, [], $version, 401),
        ]);

        try {
            $tags = $client->getTags();
        } catch (ApiException $e) {
            self::assertSame(401, $e->getCode());
            self::assertStringContainsString('401 Unauthorize', $e->getMessage());
        }

        $this->expectException(ApiException::class);
        $tags = $client->getTags();
    }
}
