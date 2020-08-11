<?php
declare(strict_types = 1);

namespace lightswitch05\PhpVersionAudit;

use lightswitch05\PhpVersionAudit\Exceptions\DownloadException;
use lightswitch05\PhpVersionAudit\Exceptions\ParseException;

final class CachedDownload
{
    const INDEX_FILE_NAME = 'index.json';
    const MAX_RETRY = 3;
    const DEFAULT_CURL_OPTS = [
        CURLOPT_FAILONERROR => true,
        CURLOPT_ACCEPT_ENCODING => '',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'php-version-audit',
    ];

    /**
     * @param string $url
     * @return string
     * @throws ParseException
     * @throws DownloadException
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
     * @throws DownloadException
     */
    public static function dom(string $url): \DOMDocument
    {
        $html = self::download($url);
        $dom = \DOMDocument::loadHTML($html, LIBXML_NOWARNING | LIBXML_NONET | LIBXML_NOERROR);
        if ($dom === false) {
            throw ParseException::fromString("Unable to parse url: " . $url);
        }
        return $dom;
    }

    /**
     * @param string $url
     * @return \stdClass
     * @throws ParseException
     * @throws DownloadException
     */
    public static function json(string $url): \stdClass
    {
        $html = self::download($url);
        return json_decode($html);
    }

    /**
     * @param string $url
     * @return string
     * @throws ParseException
     * @throws DownloadException
     */
    private static function downloadCachedFile(string $url): string
    {
        if(self::isCached($url)) {
            return self::getFileFromCache($url);
        }
        $modifiedDate = self::getServerLastModifiedDate($url);
        if (substr($url, -2) === 'gz') {
            $data = self::downloadGZipFile($url);
        } else {
            $data = self::downloadFile($url);
        }
        self::writeCacheFile($url, $data, $modifiedDate);
        return $data;
    }

    /**
     * @param string $url
     * @return string
     * @throws DownloadException
     * @throws ParseException
     */
    private static function downloadGZipFile(string $url): string
    {
        $encoded = self::downloadFile($url);
        $data = gzdecode($encoded);
        if ($data === false) {
            throw ParseException::fromString("Unable to parse file: $url");
        }
        return $data;
    }

    /**
     * @param string $url
     * @param int    $attempt
     * @return string
     * @throws DownloadException
     */
    private static function downloadFile(string $url, int $attempt = 0): string
    {
        Logger::debug('Downloading attempt ', $attempt, ': ', $url);
        $ch = curl_init($url);
        curl_setopt_array($ch, self::DEFAULT_CURL_OPTS);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response !== false) {
            return $response;
        }

        if ($attempt < self::MAX_RETRY) {
            sleep(15);
            return self::downloadFile($url, $attempt + 1);
        }
        throw DownloadException::fromString("Unable to download: $url");
    }

    /**
     * @param string $url
     * @return string
     * @throws ParseException
     */
    private static function getFileFromCache(string $url): string
    {
        Logger::debug('Loading file from cache: ', $url);
        $filename = self::urlToFileName($url);
        $fullPath = self::getCachePath($filename);
        if (!is_file($fullPath)) {
            throw ParseException::fromString("Cached file not found: $fullPath");
        }
        $contents = file_get_contents($fullPath);
        if($contents === false) {
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
            Logger::debug('Cache does not exist for ', $url);
            return false;
        }
        $lastModifiedDate = DateHelpers::fromISO8601($cacheIndex->$url->lastModifiedDate);
        $expired = self::isExpired($url, $lastModifiedDate);
        if ($expired) {
            Logger::debug('Cache has expired for ', $url);
        } else {
            Logger::debug('Cache is valid for ', $url);
        }
        return !$expired;
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
            Logger::debug('Cache time under 3600: ', $url);
            return false;
        }
        $serverLastModifiedDate = self::getServerLastModifiedDate($url);
        return $serverLastModifiedDate->getTimestamp() > $lastModifiedTimestamp;
    }

    /**
     * @param string $url
     * @param int    $attempt
     * @return \DateTimeImmutable
     */
    private static function getServerLastModifiedDate(string $url, int $attempt = 0): \DateTimeImmutable
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, self::DEFAULT_CURL_OPTS);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FILETIME,  true);
        $response = curl_exec($ch);
        $fileTime = curl_getinfo($ch,  CURLINFO_FILETIME);
        curl_close($ch);
        if ($response !== false && $fileTime > -1) {
            return DateHelpers::fromTimestamp($fileTime);
        }

        if ($response === false && $attempt < self::MAX_RETRY) {
            sleep(15);
            return self::getServerLastModifiedDate($url, $attempt + 1);
        }

        // Fall back on assuming it was just updated
        return new \DateTimeImmutable();
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
            Logger::debug('Cache index not found, creating new one.');
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
        return __DIR__ . "/../tmp/$filename";
    }
}
