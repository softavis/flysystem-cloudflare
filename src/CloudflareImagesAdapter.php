<?php

declare(strict_types=1);

namespace Softavis\Flysystem\Cloudflare;

use DateTime;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

final class CloudflareImagesAdapter implements FilesystemAdapter
{
    public function __construct(
        private readonly Client $client,
    )
    {
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
        // Cloudflare image does not have directories
    }

    public function createDirectory(string $path, Config $config): void
    {
        // Cloudflare image does not have directories
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
        return $this->client->list();
    }

    public function move(string $source, string $destination, Config $config): void
    {
        if ($source === $destination) {
            throw UnableToMoveFile::sourceAndDestinationAreTheSame($source, $destination);
        }

        try {
            $this->client->upload($destination, file_get_contents($source), $config);
        } catch (\Throwable $e) {
            throw UnableToMoveFile::because($e->getMessage(), $source, $destination);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        if ($source === $destination) {
            throw UnableToMoveFile::sourceAndDestinationAreTheSame($source, $destination);
        }

        try {
            $current = $this->client->read($source);
            $this->delete($source);
            $this->client->upload($destination, file_get_contents($current), $config);
        } catch (\Throwable $e) {
            throw UnableToMoveFile::because($e->getMessage(), $source, $destination);
        }
    }

    private function fetchImageAttribute($path): FileAttributes
    {
        try {
            $response = $this->client->get($path);
        } catch (\Throwable $e) {
            throw UnableToSetVisibility::atLocation($path, $e->getMessage(), $e);
        }

        try {
            $uploaded = new DateTime($response['uploaded']);
        } catch (\Exception $e) {
            $uploaded = new DateTime();
        }

        $metadata = $response['metadata'];

        return new FileAttributes(
            path: $path,
            fileSize: $metadata['fileSize'] ?? null,
            visibility: $response['requireSignedURLs'],
            lastModified: $uploaded->getTimestamp(),
            mimeType: $metadata['mimeType'] ?? null,
            extraMetadata: $metadata,
        );
    }
}