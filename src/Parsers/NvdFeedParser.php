<?php
declare(strict_types=1);


namespace lightswitch05\PhpVersionAudit\Parsers;

use lightswitch05\PhpVersionAudit\CachedDownload;
use lightswitch05\PhpVersionAudit\CveDetails;
use lightswitch05\PhpVersionAudit\CveId;
use lightswitch05\PhpVersionAudit\DateHelpers;

final class NvdFeedParser
{
    private static $CVE_START_YEAR = 2002;

    /**
     * @param CveId[] $cveIds
     * @param \DateTimeImmutable|null $lastUpdate
     * @return array
     */
    public static function run(array $cveIds, ?\DateTimeImmutable $lastUpdate = null): array
    {
        ini_set('memory_limit', '1024M');
        $feeds = ['modified', 'recent'];
        $cvesById = array_flip($cveIds);
        if (self::doFullUpdate($lastUpdate)) {
            // include entire database feed update
            $currentYear = date("Y");
            for($cveYear = self::$CVE_START_YEAR; $cveYear <= $currentYear; $cveYear++) {
                $feeds[] = (string)$cveYear;
            }
        }

        $cveDetails = [];
        foreach ($feeds as $feed) {
            $cveDetails = array_merge($cveDetails, self::parseFeed($cvesById, $feed));
        }
        return CveDetails::sort($cveDetails);
    }

    /**
     * @param array  $cveIds
     * @param string $feedName
     * @return array
     */
    private static function parseFeed(array $cveIds, string $feedName): array
    {
        $cveDetails = [];
        $cveFeed = CachedDownload::json("https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-$feedName.json.gz");
        $cveItems = $cveFeed->CVE_Items;
        $cveFeed = null; // free memory as fast as possible since this is very memory heavy
        foreach($cveItems as $cveItem) {
            $cve = self::parseCveItem($cveItem);
            if ($cve && isset($cveIds[(string)$cve->getId()])) {
                $cveDetails[] = $cve;
            }
        }
        return $cveDetails;
    }

    /**
     * @param \stdClass $cveItem
     * @return CveDetails|null
     */
    private static function parseCveItem(\stdClass $cveItem): ?CveDetails
    {
        if (!isset($cveItem->cve->CVE_data_meta->ID)) {
            return null;
        }
        if(!$id = CveId::fromString($cveItem->cve->CVE_data_meta->ID)){
            return null;
        }
        $publishedDate = DateHelpers::fromCveFormatToISO8601($cveItem->publishedDate);
        $lastModifiedDate = DateHelpers::fromCveFormatToISO8601($cveItem->lastModifiedDate);
        $description = null;
        $baseScore = null;
        if (isset($cveItem->cve->description->description_data)) {
            foreach ($cveItem->cve->description->description_data as $description) {
                if ($description->lang == 'en') {
                    $description = $description->value;
                    break;
                }
            }
        }

        if (isset($cveItem->impact->baseMetricV3->cvssV3->baseScore)) {
            $baseScore = $cveItem->impact->baseMetricV3->cvssV3->baseScore;
        } else if (isset($cveItem->impact->baseMetricV2->cvssV2->baseScore)) {
            $baseScore = $cveItem->impact->baseMetricV2->cvssV2->baseScore;
        }
        return new CveDetails($id, $baseScore, $publishedDate, $lastModifiedDate, $description);
    }

    private static function doFullUpdate(?\DateTimeImmutable $lastUpdate): bool
    {
        if (!$lastUpdate) {
            return true;
        }
        $elapsedTime = DateHelpers::nowTimestamp() - $lastUpdate->getTimestamp();
        return $elapsedTime > 259200; // do a full update if the last update is older then 3 days
    }
}
