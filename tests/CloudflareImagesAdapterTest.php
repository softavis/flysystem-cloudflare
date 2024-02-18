<?php

declare(strict_types=1);

namespace Softavis\Flysystem\Cloudflare\Tests;

use League\Flysystem\Config;
use League\Flysystem\UnableToReadFile;
use Softavis\Flysystem\Cloudflare\Client;
use Softavis\Flysystem\Cloudflare\CloudflareImagesAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CloudflareImagesAdapterTest extends TestCase
{
    public function testFileExistsReturnSuccess(): void
    {
        $adapter = $this->getAdapter($this->getMockHttpClient('get-success.json'));

        $response = $adapter->fileExists('some-image.jpeg');

        $this->assertTrue($response);
    }

    public function testFileExistsReturnFailure(): void
    {
        $adapter = $this->getAdapter($this->getMockHttpClient('get-not-found.json', 404));

        $response = $adapter->fileExists('non-existing-image.jpeg');

        $this->assertFalse($response);
    }

    public function testReadReturnSuccess(): void
    {
        $adapter = $this->getAdapter($this->getMockHttpClient('read-success.txt'));

        $response = $adapter->read('some-image.jpeg');

        $this->assertNotEmpty($response);
    }

    public function testReadReturnException(): void
    {
        $adapter = $this->getAdapter($this->getMockHttpClient('read-not-found.json', 404));

        $this->expectException(UnableToReadFile::class);

        $adapter->read('missing-image.jpeg');
    }

    public function testWriteReturnSuccess(): void
    {
        $adapter = $this->getAdapter($this->getMockHttpClient('write-success.json'));

        $response = $adapter->read('some-image.jpeg');

        $this->assertNotEmpty($response);
    }

    public function testWriteReturnException(): void
    {
        $adapter = $this->getAdapter($this->getMockHttpClient('write-success.json'));

        $fileContent = file_get_contents(__DIR__.'/files/example.png');

        $adapter->write('634ycoccv2d89026x6eq.jpg', $fileContent, new Config());

        $this->expectNotToPerformAssertions();
    }

    private function getAdapter(HttpClientInterface $client): CloudflareImagesAdapter
    {
        return new CloudflareImagesAdapter(new Client($client));
    }

    private function getMockHttpClient(string $fileResponse, int $statusCode = 200): MockHttpClient
    {
        $body = file_get_contents(__DIR__ . '/responses/'.$fileResponse);

        $response = new MockResponse($body, ['http_code' => $statusCode]);

        return new MockHttpClient($response);
    }
}
