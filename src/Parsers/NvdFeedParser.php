<?php

declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Parsers;

use lightswitch05\PhpVersionAudit\CachedDownload;
use lightswitch05\PhpVersionAudit\CveDetails;
use lightswitch05\PhpVersionAudit\CveId;
use lightswitch05\PhpVersionAudit\DateHelpers;
use lightswitch05\PhpVersionAudit\Exceptions\DownloadException;
use lightswitch05\PhpVersionAudit\Exceptions\ParseException;
use lightswitch05\PhpVersionAudit\Logger;

final class NvdFeedParser
{
    private static int $CVE_START_YEAR = 2002;

    /**
     * @param array<string> $cveIds
     * @return array<string, CveDetails>
     * @throws ParseException
     */
    public static function run(array $cveIds): array
    {
        ini_set('memory_limit', '2048M');
        $feedNames = ['modified', 'recent'];
        $cvesById = array_flip($cveIds);
        $currentYear = (int) date('Y');
        for ($cveYear = self::$CVE_START_YEAR; $cveYear <= $currentYear; $cveYear++) {
            $feedNames[] = (string)$cveYear;
        }

        $cveDetails = [];
        foreach ($feedNames as $feedName) {
            $cveDetails = array_merge($cveDetails, self::parseFeed($cvesById, $feedName));
        }
        uksort(
            $cveDetails,
            fn (string $first, string $second): int =>
                CveId::fromString($first)->compareTo(CveId::fromString($second))
        );
        return $cveDetails;
    }

    /**
     * @param array<array-key, mixed> $cveIds
     * @return array<string, CveDetails>
     * @throws ParseException
     */
    private static function parseFeed(array $cveIds, string $feedName): array
    {
        Logger::info('Beginning NVD feed parse: ', $feedName);
        $cveDetails = [];
        $cveFeed = self::downloadFeed($feedName);

        $cveItems = $cveFeed->CVE_Items;
        $cveFeed = null; // free memory as fast as possible since this is very memory heavy
        foreach ($cveItems as $cveItem) {
            $cve = self::parseCveItem($cveItem);
            if ($cve && isset($cveIds[(string)$cve->getId()])) {
                $cveDetails[(string)$cve->getId()] = $cve;
            }
        }
        return $cveDetails;
    }

    /**
     * @throws ParseException
     */
    private static function downloadFeed(string $feedName): \stdClass
    {
        try {
            return CachedDownload::json("https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-$feedName.json.gz");
        } catch (DownloadException $ex) {
            if ($feedName === date('Y') && date('n') === '1') {
                Logger::warning('Unable to download feed ', $feedName, '. Skipping due to beginning of the year.');
                return (object) [
                    'CVE_Items' => [],
                ];
            }
            throw ParseException::fromException($ex, __FILE__, __LINE__);
        }
    }

    private static function parseCveItem(\stdClass $cveItem): ?CveDetails
    {
        if (!isset($cveItem->cve->CVE_data_meta->ID)) {
            return null;
        }
        $id = CveId::fromString($cveItem->cve->CVE_data_meta->ID);
        if ($id === null) {
            return null;
        }
        $publishedDate = DateHelpers::fromCveFormatToISO8601($cveItem->publishedDate);
        $lastModifiedDate = DateHelpers::fromCveFormatToISO8601($cveItem->lastModifiedDate);
        $description = null;
        $baseScore = null;
        if (isset($cveItem->cve->description->description_data)) {
            foreach ($cveItem->cve->description->description_data as $description) {
                if ($description->lang === 'en') {
                    $description = $description->value;
                    break;
                }
            }
        }

        if (isset($cveItem->impact->baseMetricV3->cvssV3->baseScore)) {
            $baseScore = $cveItem->impact->baseMetricV3->cvssV3->baseScore;
        } elseif (isset($cveItem->impact->baseMetricV2->cvssV2->baseScore)) {
            $baseScore = $cveItem->impact->baseMetricV2->cvssV2->baseScore;
        }
        return new CveDetails($id, $baseScore, $publishedDate, $lastModifiedDate, $description);
    }
}
