<?php

declare(strict_types=1);

namespace Softavis\Flysystem\Cloudflare;

use finfo;
use League\Flysystem\Config;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Client
{
    private const DEFAULT_LIMIT = 1000;
    private const DEFAULT_ORDER = 'DESC';

    private const API_VERSION = 'v1';

    private const METHOD_GET = 'GET';
    private const METHOD_POST = 'POST';
    private const METHOD_PATCH = 'PATCH';
    private const METHOD_DELETE = 'DELETE';

    public function __construct(private readonly HttpClientInterface $client)
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function get(string $imageId): array
    {
        return $this->client->request(self::METHOD_GET, self::API_VERSION.'/'.$imageId)->toArray();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function read(string $imageId): string
    {
        return $this->client->request(self::METHOD_GET, self::API_VERSION.'/'.$imageId.'/blob')->getContent();
    }

    public function list(int $perPage = self::DEFAULT_LIMIT, string $order = self::DEFAULT_ORDER): array
    {
        $images = [];
        $continuationToken = null;
        do {
            /**
             * @var array{
             *     result: array{
             *      images: array{
             *          id: string,
             *          filename: string,
             *          meta: array<string, string>,
             *          requireSignedURLs: bool,
             *          uploaded: string,
             *          variants: string[]
             *      },
             *      continuation_token: ?string
             *     },
             *     success: bool,
             *     errors: string[],
             *     messages: string[]
             * } $response
             */
            $response = $this->client->request(self::METHOD_GET, 'v2/', [
                'query' => [
                    'per_page' => $this->validateLimit($perPage),
                    'sort_order' => $this->validateOrder($order),
                    'continuation_token' => $continuationToken,
                ]
            ]);
            $images += $response['result']['images'];
        } while (null !== $continuationToken = $response['result']['continuation_token']);

        return $images;
    }

    /**
     * @param string $path
     * @param string|resource $content
     * @param Config $config
     *
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function upload(string $path, mixed $content, Config $config): array
    {
        $visibility = $config->get(Config::OPTION_DIRECTORY_VISIBILITY, false);
        $metadata = $this->buildMetadata($content);

        return $this->client->request(self::METHOD_POST, self::API_VERSION, [
            'body' => [
                'id' => $path,
                'file' => $content,
                'metadata' => $metadata,
                'requireSignedURLs' => $visibility,
            ]
        ])->toArray();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function update(string $imageId, Config $config): array
    {
        $visibility = $config->get(Config::OPTION_DIRECTORY_VISIBILITY, false);

        return $this->client->request(self::METHOD_PATCH, self::API_VERSION.'/'.$imageId, [
            'body' => [
                'requireSignedURLs' => $visibility,
            ]
        ])->toArray();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function delete(string $imageId): array
    {
        return $this->client->request(self::METHOD_DELETE, self::API_VERSION.'/'.$imageId)->toArray();
    }

    private function validateOrder(string $order): string
    {
        return in_array($order, ['ASC', 'DESC']) ? $order : self::DEFAULT_ORDER;
    }

    private function validateLimit(int $limit): int
    {
        return $limit >= 10 && $limit <= 1000 ? $limit : self::DEFAULT_LIMIT;
    }

    private function buildMetadata(string $contents): array
    {
        $fileInfo = new finfo(FILEINFO_NONE);

        [$width, $height] = getimagesizefromstring($contents);

        return [
            'width' => $width,
            'height' => $height,
            'fileSize' => strlen($contents),
            'mimeType' => $fileInfo->buffer($contents, FILEINFO_MIME_TYPE),
            'extension' => $fileInfo->buffer($contents, FILEINFO_EXTENSION),
        ];
    }
}