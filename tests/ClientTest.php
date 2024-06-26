<?php

declare(strict_types=1);

namespace Softavis\Flysystem\Cloudflare\Tests;

use League\Flysystem\Config;
use Softavis\Flysystem\Cloudflare\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

class ClientTest extends TestCase
{
    private const ACCOUNT_ID = 'access-id';
    private const ACCESS_TOKEN = 'access-token';

    public function testGetReturnSuccess(): void
    {
        $mockClient = $this->getMockHttpClient('request-get-success.json');

        $client = new Client($mockClient, self::ACCOUNT_ID, self::ACCESS_TOKEN);

        $response = $client->get('some-image.jpeg');

        $this->assertTrue($response['success']);

        $this->assertArrayHasKey('id', $response['result']);
        $this->assertArrayHasKey('filename', $response['result']);
        $this->assertArrayHasKey('meta', $response['result']);
        $this->assertArrayHasKey('uploaded', $response['result']);
        $this->assertArrayHasKey('requireSignedURLs', $response['result']);
        $this->assertArrayHasKey('variants', $response['result']);
    }

    public function testGetReturnError(): void
    {
        $mockClient = $this->getMockHttpClient('request-get-error.json', 404);

        $client = new Client($mockClient, self::ACCOUNT_ID, self::ACCESS_TOKEN);

        $this->expectException(ClientExceptionInterface::class);

        $client->get('some-image.jpeg');
    }

    public function testReadReturnSuccess(): void
    {
        $mockClient = $this->getMockHttpClient('request-read-success.txt');

        $client = new Client($mockClient, self::ACCOUNT_ID, self::ACCESS_TOKEN);

        $response = $client->read('some-image.jpeg');

        $this->assertSame('image-content', $response);
    }

    public function testListReturnSuccess(): void
    {
        $responseWithToken = file_get_contents(__DIR__ . '/responses/request-list-with-token-success.json');
        $responseWithoutToken = file_get_contents(__DIR__ . '/responses/request-list-success.json');

        $mockHttpClient = new MockHttpClient([
            new MockResponse($responseWithToken, ['http_code' => 200]),
            new MockResponse($responseWithoutToken, ['http_code' => 200]),
        ]);

        $client = new Client($mockHttpClient, self::ACCOUNT_ID, self::ACCESS_TOKEN);

        $images = $client->list('/thumbnails');

        $this->assertCount(3, $images);
        foreach ($images as $image) {
            $this->assertArrayHasKey('id', $image);
            $this->assertArrayHasKey('filename', $image);
            $this->assertArrayHasKey('meta', $image);
            $this->assertArrayHasKey('uploaded', $image);
            $this->assertArrayHasKey('requireSignedURLs', $image);
            $this->assertArrayHasKey('variants', $image);
        }
    }

    public function testUploadReturnSuccess(): void
    {
        $image = file_get_contents(__DIR__.'/files/example.png');

        $mockClient = $this->getMockHttpClient('request-upload-success.json');

        $client = new Client($mockClient, self::ACCOUNT_ID, self::ACCESS_TOKEN);

        $response = $client->upload('new-image.png', $image, new Config());

        $this->assertTrue($response['success']);

        $this->assertArrayHasKey('id', $response['result']);
        $this->assertArrayHasKey('filename', $response['result']);
        $this->assertArrayHasKey('meta', $response['result']);
        $this->assertArrayHasKey('uploaded', $response['result']);
        $this->assertArrayHasKey('requireSignedURLs', $response['result']);
        $this->assertArrayHasKey('variants', $response['result']);
    }

    public function testUpdateReturnSuccess(): void
    {
        $mockClient = $this->getMockHttpClient('request-update-success.json');

        $client = new Client($mockClient, self::ACCOUNT_ID, self::ACCESS_TOKEN);

        $response = $client->update('new-image.png', new Config());

        $this->assertTrue($response['success']);

        $this->assertArrayHasKey('id', $response['result']);
        $this->assertArrayHasKey('filename', $response['result']);
        $this->assertArrayHasKey('meta', $response['result']);
        $this->assertArrayHasKey('uploaded', $response['result']);
        $this->assertArrayHasKey('requireSignedURLs', $response['result']);
        $this->assertArrayHasKey('variants', $response['result']);
    }

    public function testDeleteReturnSuccess(): void
    {
        $mockClient = $this->getMockHttpClient('request-delete-success.json');

        $client = new Client($mockClient, self::ACCOUNT_ID, self::ACCESS_TOKEN);

        $response = $client->delete('new-image.png');

        $this->assertTrue($response['success']);
    }

    private function getMockHttpClient(string $fileResponse, int $statusCode = 200): MockHttpClient
    {
        $body = file_get_contents(__DIR__ . '/responses/'.$fileResponse);

        $response = new MockResponse($body, ['http_code' => $statusCode]);

        return new MockHttpClient($response);
    }
}
