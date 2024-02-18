<?php

declare(strict_types=1);

namespace Softavis\Flysystem\Cloudflare\Tests;

use Softavis\Flysystem\Cloudflare\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ClientTest extends TestCase
{
    public function testGetReturnSuccess(): void
    {
        $client = new Client($this->getMockHttpClient('get-success.json'));

        $response = $client->get('some-image.jpeg');

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('id', $response['result']);
        $this->assertArrayHasKey('filename', $response['result']);
        $this->assertArrayHasKey('meta', $response['result']);
        $this->assertArrayHasKey('uploaded', $response['result']);
        $this->assertArrayHasKey('requireSignedURLs', $response['result']);
        $this->assertArrayHasKey('variants', $response['result']);
    }

    private function getMockHttpClient(string $fileResponse, int $statusCode = 200): MockHttpClient
    {
        $body = file_get_contents(__DIR__ . '/responses/'.$fileResponse);

        $response = new MockResponse($body, ['http_code' => $statusCode]);

        return new MockHttpClient($response);
    }
}
