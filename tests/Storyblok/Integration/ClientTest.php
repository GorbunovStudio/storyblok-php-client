<?php

declare(strict_types=1);

namespace Storyblok\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Storyblok\Client;

/**
 * @internal
 */
#[CoversNothing]
#[Group('integration')]
final class ClientTest extends TestCase
{
    public function testGetSpace(): void
    {
        $client = new Client('Iw3XKcJb6MwkdZEwoQ9BCQtt');
        $space = $client->get('spaces/me/', $client->getApiParameters());
        self::assertArrayHasKey('space', $space->httpResponseBody);
        self::assertCount(5, $space->httpResponseBody['space']);
        self::assertEquals('40101', $space->httpResponseBody['space']['id']);
    }

    public function testGetLinks(): void
    {
        $client = new Client('Iw3XKcJb6MwkdZEwoQ9BCQtt');
        $client->editMode(false);
        $links = $client->get('links', $client->getApiParameters());
        self::assertArrayHasKey('links', $links->httpResponseBody);
        self::assertCount(11, $links->httpResponseBody['links']);
    }

    public function testGetAllStories(): void
    {
        $client = new Client('Iw3XKcJb6MwkdZEwoQ9BCQtt');
        $options = $client->getApiParameters();
        $options['per_page'] = 3;
        $stories = $client->getAll('stories/', $options);
        self::assertCount(8, $stories);
    }

    public function testGetAllStoriesWithDefaultPagination(): void
    {
        $client = new Client('Iw3XKcJb6MwkdZEwoQ9BCQtt');
        $options = $client->getApiParameters();
        $stories = $client->getAll('stories/', $options);
        self::assertCount(8, $stories);
    }

    public function testGetAllResponsesStories(): void
    {
        $client = new Client('Iw3XKcJb6MwkdZEwoQ9BCQtt');
        $options = $client->getApiParameters();
        $options['per_page'] = 3;
        $responses = $client->getAll('stories/', $options, true);
        self::assertCount(3, $responses);
    }

    // useful for testing and reproduce the issue: https://github.com/storyblok/php-client/issues/54
    public function testGetOneStoryWithStoryblokPublished(): void
    {
        $client = new Client('Iw3XKcJb6MwkdZEwoQ9BCQtt');
        $options = $client->getApiParameters();
        $_GET['_storyblok_published'] = 129972584;
        $responses = $client->getStoryBySlug('home');
        $body = $responses->getBody();
        self::assertArrayHasKey('story', $body);
        self::assertArrayHasKey('name', $body['story']);
        self::assertEquals('home', $body['story']['name']);
    }

    public function testGetAllResponsesStoriesWithDefaultPagination(): void
    {
        $client = new Client('Iw3XKcJb6MwkdZEwoQ9BCQtt');
        $options = $client->getApiParameters();
        $responses = $client->getAll('stories/', $options, true);
        self::assertCount(1, $responses);
    }

    public function testCastingAfterEnrichingContent(): void
    {
        $client = new Client('Iw3XKcJb6MwkdZEwoQ9BCQtt');
        $client->editMode();
        $options = $client->getApiParameters();
        $response = $client->getStoryBySlug('home');
        $body = $response->getBody();
        self::assertIsArray($body);
        self::assertArrayHasKey('story', $body);
        self::assertCount(4, $body);
        $story = $body['story'];
        self::assertIsArray($story);
        self::assertArrayHasKey('content', $story);
        self::assertCount(22, $story);
        $content = $story['content'];
        self::assertIsArray($content);
        self::assertArrayHasKey('_uid', $content);
        self::assertArrayHasKey('body', $content);
        self::assertArrayHasKey('component', $content);
        self::assertCount(4, $content);
        self::assertIsString($content['_uid']);
        self::assertIsString($content['component']);
    }

    public function testGetOneStoryWithOptionWithCache(): void
    {
        unset($_GET['_storyblok_published']);
        $client = new Client('Iw3XKcJb6MwkdZEwoQ9BCQtt');
        $client->setCache('filesystem', ['path' => 'cache']);
        $slug = 'home';
        $key = 'stories/' . $slug;
        $cacheKey = hash('sha256', $key);
        $cachedItem = $client->getCachedItem($cacheKey);
        self::assertFalse($cachedItem->isHit());
        $options = $client->getApiParameters();
        $client->resolveLinks('url');
        $responses = $client->getStoryBySlug($slug);
        $body = $responses->getBody();
        $cv = $responses->getCacheVersion();
        self::assertArrayHasKey('story', $body);
        self::assertArrayHasKey('name', $body['story']);
        self::assertEquals('home', $body['story']['name']);
        self::assertArrayHasKey('cv', $body);
        self::assertIsNumeric($body['cv']);
        self::assertIsNumeric($cv);
        $key = 'stories/' . $slug;
        $cacheKey = hash('sha256', $key);
        $cachedItem = $client->getCachedItem($cacheKey);
        self::assertTrue($cachedItem->isHit());
        $cv1 = $responses->getBody()['cv'];
        $client2 = new Client('Iw3XKcJb6MwkdZEwoQ9BCQtt');
        $client2->setCache('filesystem', ['path' => 'cache']);
        $responses2 = $client2->getStoryBySlug($slug);
        $cv2 = $responses2->getBody()['cv'];
        self::assertEquals($cv1, $cv2);
        $identifier2 = $responses2->getHeaders()['X-Request-Id'];
        $identifier1 = $responses->getHeaders()['X-Request-Id'];
        self::assertEquals($identifier1[0], $identifier2[0]);
        $_GET['_storyblok_published'] = 129972584;
        $client3 = new Client('Iw3XKcJb6MwkdZEwoQ9BCQtt');
        $client3->setCache('filesystem', ['path' => 'cache']);
        $responses3 = $client3->getStoryBySlug($slug);
        $cv3 = $responses3->getBody()['cv'];
        self::assertEquals($cv1, $cv3);
        $identifier3 = $responses3->getHeaders()['X-Request-Id'];
        $identifier1 = $responses->getHeaders()['X-Request-Id'];
        self::assertNotEquals($identifier1[0], $identifier3[0]);
    }

    public function testGetOneStoryWithResolvedRelations(): void
    {
        unset($_GET['_storyblok_published']);
        $client = new Client('HMqBPn2a92FjXYI3tQGDVQtt');
        $slug = 'home';
        $key = 'stories/' . $slug;
        $client->editMode();
        $options = $client->getApiParameters();
        $client->resolveRelations(
            'page.RelatedProducts,page.MainProduct,page.FeaturedCategoryProducts,ProductCategory.Products,Product.ProductVariants'
        );
        $responses = $client->getStoryBySlug($slug);
        $body = $responses->getBody();
        $this->assertArrayHasKeys(['rel_uuids', 'story', 'cv', 'links'], $body);
        self::assertIsArray($body['story']['content']['FeaturedCategoryProducts']);
        self::assertEquals('Category 001', $body['story']['content']['FeaturedCategoryProducts']['0']['name']);
        self::assertEquals('Category A', $body['story']['content']['FeaturedCategoryProducts']['1']['name']);
        self::assertEquals('Category B', $body['story']['content']['FeaturedCategoryProducts']['2']['name']);
        self::assertCount(3, $body['story']['content']['FeaturedCategoryProducts']);
        self::assertIsArray($body['story']['content']['MainProduct']);
        self::assertEquals('Bike 001', $body['story']['content']['MainProduct']['name']);
        self::assertIsArray($body['story']['content']['MainProduct']['content']['ProductVariants']);
        self::assertCount(2, $body['story']['content']['MainProduct']['content']['ProductVariants']);
        self::assertCount(51, $body['rel_uuids']);
    }

    public function testGetOneStoryWithFewResolvedRelations(): void
    {
        unset($_GET['_storyblok_published']);
        $client = new Client('HMqBPn2a92FjXYI3tQGDVQtt');
        $slug = 'categories/category-shoe-001';
        $key = 'stories/' . $slug;
        $client->editMode();
        $options = $client->getApiParameters();
        $client->resolveRelations(
            'ProductCategory.Products,Product.ProductVariants'
        );
        $responses = $client->getStoryBySlug($slug);
        $body = $responses->getBody();
        $this->assertArrayHasKeys(['rels', 'story', 'cv', 'links'], $body);
        self::assertIsArray($body['story']['content']['Products']);
        self::assertCount(1, $body['story']['content']['Products']);
        self::assertEquals('Shoe 001', $body['story']['content']['Products'][0]['content']['productname']);
        self::assertIsArray($body['story']['content']['Products'][0]['content']['ProductVariants']);
        self::assertCount(3, $body['story']['content']['Products'][0]['content']['ProductVariants']);
        self::assertIsArray($body['story']['content']['Products'][0]['content']['ProductVariants']['0']);
        self::assertEquals('Shoe 001 Blue', $body['story']['content']['Products'][0]['content']['ProductVariants']['0']['name']);
        self::assertIsArray($body['story']['content']['Products'][0]['content']['ProductVariants']['0']['content']);
        self::assertEquals('Shoe 001 Blue', $body['story']['content']['Products'][0]['content']['ProductVariants']['0']['content']['VariantName']);
        self::assertCount(4, $body['rels']);
    }

    public function testGetOneStoryFromProduct(): void
    {
        unset($_GET['_storyblok_published']);
        $client = new Client('HMqBPn2a92FjXYI3tQGDVQtt');
        $slug = 'categories/products/bike-001';
        $key = 'stories/' . $slug;
        $client->editMode();
        $options = $client->getApiParameters();
        $client->resolveRelations(
            'Product.ProductVariants'
        );
        $responses = $client->getStoryBySlug($slug);
        $body = $responses->getBody();
        $this->assertArrayHasKeys(['rels', 'story', 'cv', 'links'], $body);
        // print_r($body);
        self::assertEquals('Bike 001', $body['story']['content']['productname']);
        self::assertIsArray($body['story']['content']['ProductVariants']);
        self::assertCount(2, $body['story']['content']['ProductVariants']);
        self::assertIsString($body['story']['content']['ProductVariants']['0']['name']);
        self::assertEquals('Bike 001 L', $body['story']['content']['ProductVariants']['0']['name']);
        self::assertEquals('Bike 001 L', $body['story']['content']['ProductVariants']['0']['content']['VariantName']);
    }

    public function testGetOneStoryWithUnresolvedCategoryProducts(): void
    {
        unset($_GET['_storyblok_published']);
        $client = new Client('HMqBPn2a92FjXYI3tQGDVQtt');
        $slug = 'home';
        $key = 'stories/' . $slug;
        $client->editMode();
        $options = $client->getApiParameters();
        $client->resolveRelations(
            'page.MainProduct,ProductCategory.Products,Product.ProductVariants'
        );
        $responses = $client->getStoryBySlug($slug);
        $body = $responses->getBody();
        $this->assertArrayHasKeys(['rels', 'story', 'cv', 'links'], $body);
        self::assertIsArray($body['story']['content']['FeaturedCategoryProducts']);
        self::assertCount(3, $body['story']['content']['FeaturedCategoryProducts']);
        self::assertIsString($body['story']['content']['FeaturedCategoryProducts']['0']);
        self::assertIsString($body['story']['content']['FeaturedCategoryProducts']['1']);
        self::assertIsString($body['story']['content']['FeaturedCategoryProducts']['2']);
        self::assertIsArray($body['story']['content']['MainProduct']);
        self::assertEquals('Bike 001', $body['story']['content']['MainProduct']['name']);
        self::assertIsArray($body['story']['content']['MainProduct']['content']['ProductVariants']);
        self::assertCount(2, $body['story']['content']['MainProduct']['content']['ProductVariants']);
        self::assertEquals('Bike 001 L', $body['story']['content']['MainProduct']['content']['ProductVariants']['0']['content']['VariantName']);
    }

    public function testGetListOfStoriesWithResolvedRelations(): void
    {
        unset($_GET['_storyblok_published']);
        $client = new Client('HMqBPn2a92FjXYI3tQGDVQtt');

        $key = 'stories/';
        $params = [
            'starts_with' => 'categories/category-shoe',
            'content_type' => 'ProductCategory',
        ];
        $client->editMode();
        $options = $client->getApiParameters();
        $client->resolveRelations(
            'ProductCategory.Products,Product.ProductVariants'
        );
        $responses = $client->getStories($params);
        $body = $responses->getBody();
        $this->assertArrayHasKeys(['rels', 'stories', 'cv', 'links'], $body);
        self::assertEquals('Category Shoe 001', $body['stories'][0]['name']);

        self::assertIsArray($body['stories'][0]['content']['Products']);
        self::assertCount(1, $body['stories'][0]['content']['Products']);
        self::assertEquals('Shoe 001', $body['stories'][0]['content']['Products'][0]['name']);
        self::assertIsArray($body['stories'][0]['content']['Products'][0]['content']['ProductVariants']);
        self::assertCount(3, $body['stories'][0]['content']['Products'][0]['content']['ProductVariants']);
        self::assertEquals('Shoe 001 Blue', $body['stories'][0]['content']['Products'][0]['content']['ProductVariants']['0']['content']['VariantName']);
    }

    public function testGetTranslatedListOfStoriesWithResolvedRelations(): void
    {
        unset($_GET['_storyblok_published']);
        $client = new Client('HMqBPn2a92FjXYI3tQGDVQtt');

        $key = 'stories/';
        $params = [
            'starts_with' => 'categories/category-shoe',
            'content_type' => 'ProductCategory',
        ];
        $client->editMode();
        $client->language('it');
        $options = $client->getApiParameters();
        $client->resolveRelations(
            'ProductCategory.Products,Product.ProductVariants'
        );
        $responses = $client->getStories($params);
        $body = $responses->getBody();
        $this->assertArrayHasKeys(['rels', 'stories', 'cv', 'links'], $body);
        self::assertEquals('Category Shoe 001', $body['stories'][0]['name']);

        self::assertIsArray($body['stories'][0]['content']['Products']);
        self::assertCount(1, $body['stories'][0]['content']['Products']);
        self::assertEquals('Shoe 001', $body['stories'][0]['content']['Products'][0]['name']);
        self::assertIsArray($body['stories'][0]['content']['Products'][0]['content']['ProductVariants']);
        self::assertCount(3, $body['stories'][0]['content']['Products'][0]['content']['ProductVariants']);
        self::assertEquals('Scarpa 001 Blu', $body['stories'][0]['content']['Products'][0]['content']['ProductVariants']['0']['content']['VariantName']);
    }

    public function testGetOneStoryWithProductVariants(): void
    {
        unset($_GET['_storyblok_published']);
        $client = new Client('HMqBPn2a92FjXYI3tQGDVQtt');
        $slug = 'categories/products/bike-001';
        $key = 'stories/' . $slug;
        $client->editMode();
        $options = $client->getApiParameters();
        $client->resolveRelations(
            'Product.ProductVariants'
        );
        $responses = $client->getStoryBySlug($slug);
        $body = $responses->getBody();
        $this->assertArrayHasKeys(['rels', 'story', 'cv', 'links'], $body);
        self::assertIsArray($body['story']['content']['ProductVariants']);
        self::assertCount(2, $body['story']['content']['ProductVariants']);
        self::assertIsString($body['story']['content']['ProductVariants']['0']['name']);
        self::assertIsString($body['story']['content']['ProductVariants']['1']['name']);
    }

    public function testStopResolvingLoop(): void
    {
        unset($_GET['_storyblok_published']);
        $client = new Client('HMqBPn2a92FjXYI3tQGDVQtt');
        $slug = 'testlevel';
        $key = 'stories/' . $slug;
        $client->editMode();
        $options = $client->getApiParameters();
        $client->resolveRelations(
            'level-1.related,level-2.related'
        );
        $responses = $client->getStoryBySlug($slug);
        $body = $responses->getBody();

        $this->assertArrayHasKeys(['rels', 'story', 'cv', 'links'], $body);
        self::assertIsArray($body['story']['content']['related']);
        self::assertEquals('TestLevel2', $body['story']['content']['related']['name']);
        self::assertEquals(1, $body['story']['content']['related']['_stopResolving']);
        self::assertEquals('TestLevel', $body['story']['content']['related']['content']['related']['name']);
        self::assertIsArray($body['story']['content']['related']['content']['related']['content']['related']);
        self::assertIsString($body['story']['content']['related']['content']['related']['content']['related']['content']['related']);
    }

    private function assertArrayHasKeys(array $keys, array $array): void
    {
        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $array);
        }
    }
}
