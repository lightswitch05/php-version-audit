<?php
declare(strict_types = 1);

namespace lightswitch05\PhpVersionAudit;

use lightswitch05\PhpVersionAudit\Exceptions\ParseException;

final class CachedDownload
{
    const INDEX_FILE_NAME = 'index.json';

    /**
     * @param string $url
     * @return string
     */
    public static function download(string $url): string
    {
        self::setup();
        return self::downloadCachedFile($url);
    }

    /**
     * @param string $url
     * @return \DOMDocument
     * @throws ParseException
     */
    public static function dom(string $url): \DOMDocument
    {
        $html = self::download($url);
        if (($dom = \DOMDocument::loadHTML($html, LIBXML_NOWARNING | LIBXML_NONET | LIBXML_NOERROR)) === false) {
            throw ParseException::fromString("Unable to parse url: " . $url);
        }
        return $dom;
    }

    public static function json(string $url): \stdClass
    {
        $html = self::download($url);
        return json_decode($html);
    }

    /**
     * @param string $url
     * @return string
     */
    private static function downloadCachedFile(string $url): string
    {
        if(self::isCached($url)) {
            return self::getFileFromCache($url);
        }
        $modifiedDate = self::getServerLastModifiedDate($url);
        if (substr($url, -2) === 'gz') {
            if (($stream = gzopen($url, 'rb')) === false){
                throw ParseException::fromString("Unable to download: $url");
            }
            if (($data = stream_get_contents($stream)) === false) {
                throw ParseException::fromString("Unable to download: $url");
            }
        } else {
            if(($data = file_get_contents($url)) === false) {
                throw ParseException::fromString("Unable to download: $url");
            }
        }
        self::writeCacheFile($url, $data, $modifiedDate);
        return $data;
    }

    /**
     * @param string $url
     * @return string
     */
    private static function getFileFromCache(string $url): string
    {
        $filename = self::urlToFileName($url);
        $fullPath = self::getCachePath($filename);
        if (!is_file($fullPath)) {
            throw ParseException::fromString("Cached file not found: $fullPath");
        }
        if(($contents = file_get_contents($fullPath)) === false) {
            throw ParseException::fromString("Unable to read cached file: $fullPath");
        }
        return $contents;
    }

    /**
     * @param string $url
     * @return bool
     */
    private static function isCached(string $url): bool
    {
        $cacheIndex = self::getCacheIndex();
        if (!isset($cacheIndex->$url)) {
            return false;
        }
        $lastModifiedDate = DateHelpers::fromISO8601($cacheIndex->$url->lastModifiedDate);
        return !self::isExpired($url, $lastModifiedDate);
    }

    /**
     * @param string $url
     * @param \DateTimeImmutable $lastModifiedDate
     * @return bool
     */
    private static function isExpired(string $url, \DateTimeImmutable $lastModifiedDate): bool
    {
        $lastModifiedTimestamp = $lastModifiedDate->getTimestamp();
        $elapsedSeconds = DateHelpers::nowTimestamp() - $lastModifiedTimestamp;
        // enforce a minimum cache of 1 hour to makeup for lack of last modified time on changelog
        if ($elapsedSeconds < 3600) {
            return false;
        }
        $serverLastModifiedDate = self::getServerLastModifiedDate($url);
        return $serverLastModifiedDate->getTimestamp() > $lastModifiedTimestamp;
    }

    /**
     * @param string $url
     * @return \DateTimeImmutable
     */
    private static function getServerLastModifiedDate(string $url): \DateTimeImmutable
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD'
            ]
        ]);
        $headers = get_headers($url, 1, $context);
        if (!isset($headers['Last-Modified'])) {
            // Always assume just updated if the header is missing
            return new \DateTimeImmutable();
        }
        return DateHelpers::fromRFC7231($headers['Last-Modified']);
    }

    /**
     * @param string $url
     * @return string
     */
    private static function urlToFileName(string $url): string
    {
        $hash = hash("sha256", $url);
        return substr($hash, 0, 15) . ".txt";
    }

    /**
     * @return void
     */
    private static function setup(): void
    {
        $tempDir = self::getCachePath();
        if (!is_dir($tempDir)) {
            mkdir($tempDir);
        }
        $indexPath = self::getCachePath(self::INDEX_FILE_NAME);
        if (!is_file($indexPath)) {
            self::saveCacheIndex(new \stdClass());
        }
    }

    /**
     * @param string $url
     * @param string $data
     * @param \DateTimeImmutable $modifiedDate
     * @return void
     */
    private static function writeCacheFile(string $url, string $data, \DateTimeImmutable $modifiedDate): void
    {
        $cacheIndex = self::getCacheIndex();
        $filename = self::urlToFileName($url);
        $cacheIndex->$url = new \stdClass();
        $cacheIndex->$url->filename = $filename;
        $cacheIndex->$url->lastModifiedDate = DateHelpers::toISO8601($modifiedDate);
        file_put_contents(self::getCachePath($filename), $data);
        self::saveCacheIndex($cacheIndex);
    }

    /**
     * @return \stdClass
     */
    private static function getCacheIndex(): \stdClass
    {
        $fullPath = self::getCachePath(self::INDEX_FILE_NAME);
        $index = file_get_contents($fullPath);
        return json_decode($index);
    }

    /**
     * @param \stdClass $index
     */
    private static function saveCacheIndex(\stdClass $index): void
    {
        $fullPath = self::getCachePath(self::INDEX_FILE_NAME);
        $data = json_encode($index, JSON_PRETTY_PRINT);
        file_put_contents($fullPath, $data);
    }

    /**
     * @param string|null $filename
     * @return string
     */
    private static function getCachePath(?string $filename = null): string
    {
        return __DIR__ . '/../tmp/' . $filename;
    }
}
