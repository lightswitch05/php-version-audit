<?php
declare(strict_types = 1);

namespace lightswitch05\PhpVersionAudit;

final class PhpVersion implements \JsonSerializable, \Stringable
{
    private const PRE_RELEASE_ALPHA = 'alpha';
    private const PRE_RELEASE_BETA = 'beta';
    private const PRE_RELEASE_CANDIDATE = 'rc';

    private function __construct(
        private int $major,
        private int $minor,
        private int $patch,
        /**
         * $var string|null
         */
        private ?string $preReleaseType,
        private ?int $preReleaseVersion
    )
    {
    }

    public static function fromString(?string $fullVersion): ?PhpVersion
    {
        if (!$fullVersion || !preg_match('#(\d+).(\d+).(\d+)\s*(release\s*candidate|rc|beta|alpha)?\s*(\d*)#i', $fullVersion, $matches)) {
            return null;
        }
        $major = (int) $matches[1];
        $minor = (int) $matches[2];
        $patch = (int) $matches[3];
        $preReleaseVersion = null;
        if($preReleaseType = self::normalizeReleaseType($matches[4])) {
            $preReleaseVersion = (int) $matches[5];
        }
        return new self($major, $minor, $patch, $preReleaseType, $preReleaseVersion);
    }

    public function compareTo(PhpVersion $otherVersion): int
    {
        if ($this->major === $otherVersion->major
            && $this->minor === $otherVersion->minor
            && $this->patch === $otherVersion->patch
            && $this->preReleaseType === $otherVersion->preReleaseType
            && $this->preReleaseVersion === $otherVersion->preReleaseVersion) {
            return 0;
        }
        if ($this->major !== $otherVersion->major) {
            return $this->major - $otherVersion->major;
        }
        if ($this->minor !== $otherVersion->minor) {
            return $this->minor - $otherVersion->minor;
        }
        if ($this->patch !== $otherVersion->patch) {
            return $this->patch - $otherVersion->patch;
        }
        if ($this->isPreRelease() && !$otherVersion->isPreRelease()) {
            return -1;
        }
        if (!$this->isPreRelease() && $otherVersion->isPreRelease()) {
            return 1;
        }
        // both are prerelease at this point
        if ($this->preReleaseType !== $otherVersion->preReleaseType) {
            return strcmp($this->preReleaseType, $otherVersion->preReleaseType);
        }
        return $this->preReleaseVersion - $otherVersion->preReleaseVersion;
    }

    private static function normalizeReleaseType(string $parsedReleaseType): ?string
    {
        $parsedReleaseType = strtolower($parsedReleaseType);
        if (in_array($parsedReleaseType, [self::PRE_RELEASE_CANDIDATE, self::PRE_RELEASE_BETA, self::PRE_RELEASE_ALPHA])) {
            return $parsedReleaseType;
        }
        if (preg_match('#release\s*candidate#', $parsedReleaseType)) {
            return self::PRE_RELEASE_CANDIDATE;
        }
        return null;
    }

    public function isPreRelease(): bool
    {
        return !empty($this->preReleaseType);
    }

    public function getMajor(): int
    {
        return $this->major;
    }

    public function getMinor(): int
    {
        return $this->minor;
    }

    public function getPatch(): int
    {
        return $this->patch;
    }

    public function getMajorMinorVersionString(): string
    {
        return "$this->major.$this->minor";
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) "$this->major.$this->minor.$this->patch$this->preReleaseType$this->preReleaseVersion";
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return (string)$this;
    }
}
