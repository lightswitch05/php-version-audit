<?php
declare(strict_types = 1);

namespace lightswitch05\PhpVersionAudit;

final class PhpRelease implements \JsonSerializable
{
    /**
     * @var PhpVersion $version
     */
    private $version;

    /**
     * @var string|null $releaseDate
     */
    private $releaseDate;

    /**
     * @var CveId[] $patchedCveIds
     */
    private $patchedCveIds;

    private function __construct(PhpVersion $version, ?string $releaseDate)
    {
        $this->version = $version;
        $this->releaseDate = $releaseDate;
        $this->patchedCveIds = [];
    }

    /**
     * @param PhpVersion $version
     * @param string|null $releaseDate
     * @param string|null $releaseDescription
     * @return PhpRelease
     */
    public static function fromReleaseDescription(PhpVersion $version, ?string $releaseDate, ?string $releaseDescription): PhpRelease
    {
        $release = new self($version, $releaseDate);
        if (!empty($releaseDescription) && preg_match_all('#CVE-\d+-\d+#i', $releaseDescription, $cveMatches)) {
            foreach ($cveMatches[0] as $match) {
                $id = CveId::fromString($match);
                if ($id !== null) {
                    $release->addPatchedCveIds($id);
                }
            }
        }
        return $release;
    }

    /**
     * @param PhpRelease[] $releases
     * @return PhpRelease[]
     */
    public static function sort(array $releases): array
    {
        $sortedReleases = array_merge([], $releases);
        usort($sortedReleases, function(PhpRelease $first, PhpRelease $second): int {
            return $first->compareTo($second);
        });
        return $sortedReleases;
    }

    /**
     * @param CveId $cveId
     */
    private function addPatchedCveIds(CveId $cveId): void
    {
        if (!in_array($cveId, $this->patchedCveIds)) {
            $this->patchedCveIds[] = $cveId;
            $this->patchedCveIds = CveId::sort($this->patchedCveIds);
        }
    }

    /**
     * @return PhpVersion
     */
    public function getVersion(): PhpVersion
    {
        return $this->version;
    }

    public function compareTo(PhpRelease $release): int
    {
        return $this->version->compareTo($release->version);
    }

    /**
     * @return CveId[]
     */
    public function getPatchedCveIds(): array
    {
        return $this->patchedCveIds;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'releaseDate' => $this->releaseDate,
            'patchedCves' => $this->patchedCveIds
        ];
    }
}
