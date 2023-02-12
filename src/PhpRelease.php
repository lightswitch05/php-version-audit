<?php
declare(strict_types = 1);

namespace lightswitch05\PhpVersionAudit;

final class PhpRelease implements \JsonSerializable
{
    /**
     * @var CveId[] $patchedCveIds
     */
    private array $patchedCveIds = [];

    private function __construct(private PhpVersion $version, private ?string $releaseDate)
    {
    }

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
        usort($sortedReleases, fn(PhpRelease $first, PhpRelease $second): int => $first->compareTo($second));
        return $sortedReleases;
    }

    private function addPatchedCveIds(CveId $cveId): void
    {
        if (!in_array($cveId, $this->patchedCveIds)) {
            $this->patchedCveIds[] = $cveId;
            $this->patchedCveIds = CveId::sort($this->patchedCveIds);
        }
    }

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
