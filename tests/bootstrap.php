<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;

require \dirname(__DIR__) . '/vendor/autoload.php';

function mockResponse($endpoint = 'stories', $headers = [], $version = 'v2', $statusCode = 200)
{
    $content = file_get_contents("./tests/Data/{$version}/{$endpoint}.json");

    return new Response($statusCode, $headers, $content);
}
