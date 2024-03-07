<?php

declare(strict_types=1);

namespace Softavis\Flysystem\Cloudflare\Tests;

use League\Flysystem\Config;
use PHPUnit\Framework\TestCase;
use Softavis\Flysystem\Cloudflare\Client;
use Softavis\Flysystem\Cloudflare\CloudflareAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CloudflareAdapterTest extends TestCase
{
    public function testFileExistsReturnSuccess(): void
    {
        $adapter = $this->getAdapter('request-get-success.json');

        $response = $adapter->fileExists('some-image.jpeg');

        $this->assertTrue($response);
    }

    public function testWriteReturnSuccess(): void
    {
        $adapter = $this->getAdapter('request-upload-success.json');

        $adapter->write('some-image.jpeg', 'image-string-content', new Config());

        $this->expectNotToPerformAssertions();
    }

    public function testWriteStreamReturnSuccess(): void
    {
        $adapter = $this->getAdapter('request-upload-success.json');

        $adapter->writeStream('some-image.jpeg', 'image-string-content', new Config());

        $this->expectNotToPerformAssertions();
    }

    public function testReadReturnSuccess(): void
    {
        $adapter = $this->getAdapter('request-get-success.json');

        $response = $adapter->read('some-image.jpeg');

        $this->assertNotEmpty($response);
    }

    public function testReadStreamReturnSuccess(): void
    {
        $adapter = $this->getAdapter('request-get-success.json');

        $response = $adapter->readStream('some-image.jpeg');

        $this->assertIsResource($response);
    }

    public function testDeleteStreamReturnSuccess(): void
    {
        $adapter = $this->getAdapter('request-delete-success.json');

        $adapter->delete('some-image.jpeg');

        $this->expectNotToPerformAssertions();
    }

    public function testDeleteDirectoryReturnSuccess(): void
    {
        $responseList = new MockResponse(file_get_contents(__DIR__.'/responses/request-list-success.json'));
        $responseDelete = new MockResponse(file_get_contents(__DIR__.'/responses/request-delete-success.json'));

        $client = new Client(new MockHttpClient([$responseList, $responseDelete]));

        $adapter = new CloudflareAdapter($client);

        $adapter->deleteDirectory('/images');

        $this->expectNotToPerformAssertions();
    }

    public function testPublicUrlReturnCorrectSuccess(): void
    {
        $imageId = 'image-id.jpeg';
        $accountHash = 'account-hash';
        $variantName = 'public';

        $config = ['accountHash' => $accountHash, 'variantName' => $variantName];
        $adapter = new CloudflareAdapter(new Client(new MockHttpClient()));

        $imageUrl = $adapter->publicUrl($imageId, new Config($config));

        $expectedUrl = "https://imagedelivery.net/{$accountHash}/{$imageId}/{$variantName}";

        $this->assertSame($expectedUrl, $imageUrl);
    }

    private function getAdapter(string $fileResponse, int $statusCode = 200): CloudflareAdapter
    {
        return new CloudflareAdapter(new Client($this->getMockHttpClient($fileResponse, $statusCode)));
    }

    private function getMockHttpClient(string $fileResponse, int $statusCode = 200): MockHttpClient
    {
        $body = file_get_contents(__DIR__ . '/responses/' . $fileResponse);

        $response = new MockResponse($body, ['http_code' => $statusCode]);

        return new MockHttpClient($response);
    }
}
