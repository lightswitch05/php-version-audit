<?php

declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit;

use lightswitch05\PhpVersionAudit\Exceptions\InvalidVersionException;
use lightswitch05\PhpVersionAudit\Exceptions\StaleRulesException;
use lightswitch05\PhpVersionAudit\Exceptions\UnknownVersionException;
use lightswitch05\PhpVersionAudit\Parsers\ChangelogParser;
use lightswitch05\PhpVersionAudit\Parsers\NvdFeedParser;
use lightswitch05\PhpVersionAudit\Parsers\SupportParser;

final class Application
{
    private \stdClass $rules;

    private ?\lightswitch05\PhpVersionAudit\PhpVersion $auditVersion = null;

    /**
     * @param bool $noUpdate disable downloading the latest rules from GitHub pages
     */
    public function __construct(string $phpVersion, bool $noUpdate)
    {
        $this->auditVersion = PhpVersion::fromString($phpVersion);
        if ($this->auditVersion === null) {
            throw InvalidVersionException::fromString($phpVersion);
        }
        $this->rules = Rules::loadRules($noUpdate);
    }

    public function getVulnerabilities(): \stdClass
    {
        $cves = [];
        $majorAndMinor = $this->auditVersion->getMajorMinorVersionString();
        $maxVersion = PhpVersion::fromString($majorAndMinor . ".9999");
        foreach ($this->rules->releases as $versionString => $release) {
            $releaseVersion = PhpVersion::fromString($versionString);
            if ($releaseVersion->compareTo($this->auditVersion) <= 0 ||
                $releaseVersion->compareTo($maxVersion) > 0) {
                continue;
            }
            $cves = array_merge($cves, $release->getPatchedCveIds());
        }
        $vulnerabilities = new \stdClass();
        foreach ($cves as $cve) {
            $cveDetails = null;
            $cveString = (string)$cve->getId();
            if (isset($this->rules->cves->$cveString)) {
                $cveDetails = $this->rules->cves->$cveString;
            }
            $vulnerabilities->$cveString = $cveDetails;
        }
        return $vulnerabilities;
    }

    public function hasVulnerabilities(): bool
    {
        return !empty((array) $this->getVulnerabilities());
    }

    public function isLatestVersion(): bool
    {
        $versionString = self::getLatestVersion();
        $latestVersion = PhpVersion::fromString($versionString);
        return $this->auditVersion->compareTo($latestVersion) === 0;
    }

    public function getLatestVersion(): string
    {
        if (!$this->rules->latestVersion) {
            throw StaleRulesException::fromString("Latest PHP version is unknown!");
        }
        return (string) $this->rules->latestVersion;
    }

    public function isLatestPatchVersion(): bool
    {
        $versionString = self::getLatestPatchVersion();
        $latestVersion = PhpVersion::fromString($versionString);
        return $this->auditVersion->compareTo($latestVersion) === 0;
    }

    public function getLatestPatchVersion(): string
    {
        $majorAndMinor = $this->auditVersion->getMajorMinorVersionString();
        if (!isset($this->rules->latestVersions->$majorAndMinor)) {
            throw UnknownVersionException::fromString((string)$this->auditVersion);
        }
        $latestPatch = $this->rules->latestVersions->$majorAndMinor;
        if ($this->auditVersion->compareTo($latestPatch) > 0) {
            throw UnknownVersionException::fromString((string)$this->auditVersion);
        }
        return (string) $latestPatch;
    }

    public function isLatestMinorVersion(): bool
    {
        $versionString = self::getLatestMinorVersion();
        $latestVersion = PhpVersion::fromString($versionString);
        return $this->auditVersion->compareTo($latestVersion) === 0;
    }

    public function getLatestMinorVersion(): string
    {
        $major = (string) $this->auditVersion->getMajor();
        if (!isset($this->rules->latestVersions->$major)) {
            throw UnknownVersionException::fromString((string)$this->auditVersion);
        }
        return (string) $this->rules->latestVersions->$major;
    }

    public function getSecuritySupportEndDate(): ?string
    {
        return $this->getSupportEndDate('security');
    }

    public function hasSecuritySupport(): bool
    {
        $endDateString = self::getSecuritySupportEndDate();
        if (!$endDateString) {
            return false;
        }
        $endDate = DateHelpers::fromISO8601($endDateString);
        return DateHelpers::nowTimestamp() - $endDate->getTimestamp() < 0;
    }

    public function getActiveSupportEndDate(): ?string
    {
        return $this->getSupportEndDate('active');
    }

    private function getSupportEndDate(string $supportType): ?string
    {
        if ($this->auditVersion->isPreRelease()) {
            return null;
        }
        $majorAndMinor = $this->auditVersion->getMajorMinorVersionString();
        if (!isset($this->rules->supportEndDates->$majorAndMinor)) {
            throw UnknownVersionException::fromString((string)$this->auditVersion);
        }
        return DateHelpers::toISO8601($this->rules->supportEndDates->$majorAndMinor->$supportType);
    }

    public function hasActiveSupport(): bool
    {
        $endDateString = self::getActiveSupportEndDate();
        if (!$endDateString) {
            return false;
        }
        $endDate = DateHelpers::fromISO8601($endDateString);
        return DateHelpers::nowTimestamp() - $endDate->getTimestamp() < 0;
    }

    public function getRulesLastUpdatedDate(): ?string
    {
        return DateHelpers::toISO8601($this->rules->lastUpdatedDate);
    }

    public function getAllAuditDetails(): \stdClass
    {
        Rules::assertFreshRules($this->rules);
        return (object) [
            'auditVersion' => (string) $this->auditVersion,
            'hasVulnerabilities' => $this->hasVulnerabilities(),
            'hasSecuritySupport' => $this->hasSecuritySupport(),
            'hasActiveSupport' => $this->hasActiveSupport(),
            'isLatestPatchVersion' => $this->isLatestPatchVersion(),
            'isLatestMinorVersion' => $this->isLatestMinorVersion(),
            'isLatestVersion' => $this->isLatestVersion(),
            'latestPatchVersion' => $this->getLatestPatchVersion(),
            'latestMinorVersion' => $this->getLatestMinorVersion(),
            'latestVersion' => $this->getLatestVersion(),
            'activeSupportEndDate' => $this->getActiveSupportEndDate(),
            'securitySupportEndDate' => $this->getSecuritySupportEndDate(),
            'rulesLastUpdatedDate' => $this->getRulesLastUpdatedDate(),
            'vulnerabilities' => $this->getVulnerabilities(),
        ];
    }

    /**
     * @note PLEASE DO NOT USE THIS. This function is intended to only be used internally for updating
     *       project rules in github, which can then be accessed by ALL instances of PHP Version Audit.
     *       Running it locally puts unnecessary load on the source servers and cannot be re-used by others.
     *
     *       The github hosted rules are setup on a cron schedule to update multiple times a day.
     *       Running it directly will not provide you with any new information and will only
     *       waste time and server resources.
     */
    public function fullRulesUpdate(): void
    {
        Logger::warning('Running full rules update, this is slow and should not be ran locally!');
        $releases = ChangelogParser::run();
        $cves = $this->loadCveDetails($releases);
        $supportEndDates = SupportParser::run();
        $this->assertExpectedRulesCount($releases, $cves, $supportEndDates);

        Rules::saveRules($releases, $cves, $supportEndDates);
        $this->rules = Rules::loadRules(true);
    }

    /**
     * @param PhpRelease[] $releases
     * @return CveDetails[]
     */
    private function loadCveDetails(array $releases): array
    {
        $cves = [];
        foreach ($releases as $release) {
            $patchedCveIds = $release->getPatchedCveIds();
            foreach ($patchedCveIds as $cveId) {
                $cves[] = $cveId->getId();
            }
        }
        return NvdFeedParser::run($cves);
    }

    /**
     * @param array<PhpRelease> $releases
     * @param array<CveDetails> $cves
     * @param array<\stdClass> $supportEndDates
     */
    private function assertExpectedRulesCount(array $releases, array $cves, array $supportEndDates): void
    {
        $currentCounts = [
            'releasesCount' => $this->rules->releasesCount,
            'cveCount' => $this->rules->cveCount,
            'supportVersionsCount' => $this->rules->supportVersionsCount,
        ];

        $newCounts = [
            'releasesCount' => count($releases),
            'cveCount' => count($cves),
            'supportVersionsCount' => count(array_keys($supportEndDates)),
        ];

        foreach ($currentCounts as $type => $currentCount) {
            if ($currentCount > $newCounts[$type]) {
                $error = "Updated rules failed to meet expected counts. {$type}: {$newCounts[$type]} < {$currentCount}";
                throw StaleRulesException::fromString($error);
            }
        }
    }
}
