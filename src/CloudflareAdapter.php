<?php

declare(strict_types=1);

namespace Softavis\Flysystem\Cloudflare;

use DateTime;
use DateTimeInterface;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

final class CloudflareAdapter implements FilesystemAdapter, PublicUrlGenerator
{
    /** @var Client $client */
    private $client;

    /** @var string */
    private $accountHash;

    /** @var string */
    private $variantName;

    public function __construct(Client $client, string $accountHash, string $variantName)
    {
        $this->client = $client;

        $this->accountHash = $accountHash;
        $this->variantName = $variantName;
    }

    public function fileExists(string $path): bool
    {
        try {
            $response = $this->client->get($path);
        } catch (\Throwable $e) {
            if ($e instanceof ClientExceptionInterface) {
                return false;
            }

            throw UnableToCheckFileExistence::forLocation($path, $e);
        }

        return $response['success'];
    }

    public function directoryExists(string $path): bool
    {
        // Cloudflare Images does not support directories
        return true;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->client->upload($path, $contents, $config);
        } catch (\Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $this->client->upload($path, $contents, $config);
        } catch (\Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function read(string $path): string
    {
        try {
            $response = $this->client->read($path);
        } catch (\Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }

        return $response;
    }

    public function readStream(string $path)
    {
        try {
            $response = $this->client->read($path);
        } catch (\Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }

        /** @var resource $stream */
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $response);
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $this->client->delete($path);
        } catch (\Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $images = $this->client->list($path);

        foreach ($images as $image) {
            $this->delete($image['id']);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        // Cloudflare Images does not have directories
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $config = new Config([
            Config::OPTION_VISIBILITY => $visibility
        ]);

        try {
            $this->client->update($path, $config);
        } catch (\Throwable $e) {
            throw UnableToSetVisibility::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->fetchImageAttribute($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->fetchImageAttribute($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->fetchImageAttribute($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->fetchImageAttribute($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return $this->client->list($path);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->client->upload($destination, file_get_contents($source), $config);
        } catch (\Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $current = $this->client->read($source);
            $this->delete($source);
            $this->client->upload($destination, file_get_contents($current), $config);
        } catch (\Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination);
        }
    }

    private function fetchImageAttribute($path): FileAttributes
    {
        try {
            $response = $this->client->get($path);
        } catch (\Throwable $e) {
            throw UnableToRetrieveMetadata::create($path, $e->getMessage(), $e->getMessage(), $e);
        }

        try {
            $uploaded = new DateTime($response['uploaded']);
        } catch (\Exception $e) {
            $uploaded = new DateTime();
        }

        $metadata = $response['metadata'];

        $fileSize = $metadata['fileSize'] ?? null;
        $visibility = $response['requireSignedURLs'];
        $lastModified = $uploaded->getTimestamp();
        $mimeType = $metadata['mimeType'] ?? null;

        return new FileAttributes($path, $fileSize, $visibility, $lastModified, $mimeType, $metadata);
    }

    public function publicUrl(string $path, Config $config): string
    {
        return strtr('https://imagedelivery.net/{accountHash}/{path}/{variant}', [
            '{accountHash}' => $config->get('accountHash', $this->accountHash),
            '{path}' => $path,
            '{variant}' => $config->get('variantName', $this->variantName)
        ]);
    }
}
