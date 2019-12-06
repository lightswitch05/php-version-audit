<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit;

use lightswitch05\PhpVersionAudit\Exceptions\StaleRulesException;

final class Rules
{
    /**
     * @var string $RULES_PATH
     */
    private static $RULES_PATH = '/../docs/rules-v1.json';

    /**
     * @var string $HOSTED_RULES_PATH
     */
    private static $HOSTED_RULES_PATH = 'https://www.github.developerdan.com/php-version-audit/rules-v1.json';

    /**
     * @param /stdCalss $rules
     */
    public static function assertFreshRules($rules): void
    {
        $elapsedSeconds = DateHelpers::nowTimestamp() - $rules->lastUpdatedDate->getTimestamp();
        if ($elapsedSeconds > 1209600) {
            throw StaleRulesException::fromString("Rules are older then two weeks");
        }
    }

    /**
     * @param bool $localRulesOnly
     * @return \stdClass
     */
    public static function loadRules(bool $noUpdate): \stdClass
    {
        $loadedRules = self::getRulesStdObject($noUpdate);
        return self::transformRules($loadedRules);
    }

    /**
     * @param bool $noUpdate
     * @return \stdClass
     */
    private static function getRulesStdObject(bool $noUpdate): \stdClass
    {
        if ($noUpdate) {
            if(!is_file(__DIR__ . self::$RULES_PATH) || !$rulesString = file_get_contents(__DIR__ . self::$RULES_PATH)) {
                throw StaleRulesException::fromString("Unable to load rules");
            }
            return json_decode($rulesString);
        }
        return CachedDownload::json(self::$HOSTED_RULES_PATH);
    }

    /**
     * @param \stdClass $rules
     * @return \stdClass
     */
    private static function transformRules(\stdClass $rules): \stdClass
    {
        if (empty($rules)
            || empty($rules->lastUpdatedDate)
            || empty($rules->latestVersions)
            || empty($rules->latestVersion)
            || empty($rules->supportEndDates)) {
            throw StaleRulesException::fromString("Unable to load rules");
        }
        $rules->lastUpdatedDate = DateHelpers::fromISO8601($rules->lastUpdatedDate);
        foreach ($rules->latestVersions as $index => $latestVersion) {
            $rules->latestVersions->$index = PhpVersion::fromString($latestVersion);
        }
        $rules->latestVersion = PhpVersion::fromString($rules->latestVersion);
        foreach ($rules->supportEndDates as $index => $dates) {
            $rules->supportEndDates->$index->active = DateHelpers::fromISO8601($dates->active);
            $rules->supportEndDates->$index->security = DateHelpers::fromISO8601($dates->security);
        }
        foreach ($rules->releases as $versionString => $release) {
            $phpVersion = PhpVersion::fromString($versionString);
            $phpRelease = PhpRelease::fromReleaseDescription($phpVersion, $release->releaseDate, json_encode($release->patchedCves));
            $rules->releases->$versionString = $phpRelease;
        }
        foreach ($rules->cves as $cveString => $cveDetails) {
            $rules->cves->$cveString = new CveDetails(
                CveId::fromString($cveDetails->id),
                (float)$cveDetails->baseScore,
                $cveDetails->publishedDate,
                $cveDetails->lastModifiedDate,
                $cveDetails->description
            );
        }
        return $rules;
    }

    /**
     * @param /stdClass $updatedRules
     * @return void
     */
    public static function saveRules($releases, $cves, $supportEndDates): void
    {
        $rules = (object) [
            'lastUpdatedDate' => DateHelpers::nowString(),
            'name' => 'PHP Version Audit',
            'website' => 'https://github.com/lightswitch05/php-version-audit',
            'licence' => 'https://github.com/lightswitch05/php-version-audit/blob/master/LICENSE',
            'source' => self::$HOSTED_RULES_PATH,
            'releasesCount' => count($releases),
            'cveCount' => count($cves),
            'supportVersionsCount' => count(array_keys($supportEndDates)),
            'latestVersion' => self::releasesToLatestVersion($releases),
            'latestVersions' => self::releasesToLatestVersions($releases),
            'supportEndDates' => $supportEndDates,
            'releases' =>  self::formatReleases($releases),
            'cves' => $cves
        ];
        self::writeRulesFile($rules);
    }

    /**
     * @param \stdClass $rules
     * @return void
     */
    private static function writeRulesFile(\stdClass $rules): void
    {
        file_put_contents(__DIR__ . self::$RULES_PATH, json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param PhpRelease[] $releases
     * @return PhpVersion
     */
    private static function releasesToLatestVersion(array $releases): PhpVersion
    {
        $latestVersion = null;
        foreach ($releases as $release) {
            $releaseVersion = $release->getVersion();
            if ($releaseVersion->isPreRelease()) {
                continue;
            }
            $latestVersion = $latestVersion ?? $releaseVersion;
            if ($releaseVersion->compareTo($latestVersion) > 0) {
                $latestVersion = $releaseVersion;
            }
        }
        return $latestVersion;
    }

     /**
     * @param PhpRelease[] $releases
     * @return \stdClass[]
     */
    private static function releasesToLatestVersions(array $releases): array
    {
        $latestVersions = [];
        foreach ($releases as $release) {
            $version = $release->getVersion();
            if ($version->isPreRelease()) {
                continue;
            }
            $major = $version->getMajor();
            $minor = $version->getMinor();
            $majorAndMinor = "$major.$minor";
            if (!isset($latestVersions[$major])) {
                $latestVersions[$major] = $version;
            }
            if (!isset($latestVersions[$majorAndMinor])) {
                $latestVersions[$majorAndMinor] = $version;
            }
            if ($version->compareTo($latestVersions[$major]) > 0) {
                $latestVersions[$major] = $version;
            }
            if ($version->compareTo($latestVersions[$majorAndMinor]) > 0) {
                $latestVersions[$majorAndMinor] = $version;
            }
        }
        return $latestVersions;
    }

    /**
     * @param PhpRelease[] $releases
     * @return string[]
     */
    private static function formatReleases(array $releases): array
    {
        $releasesByVersion = [];
        foreach ($releases as $release) {
            $releasesByVersion[(string)$release->getVersion()] = $release;
        }
        return $releasesByVersion;
    }
}
