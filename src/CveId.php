<?php

declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit;

final class CveId implements \JsonSerializable, \Stringable
{
    private int $year;

    private int $sequenceNumber;

    private function __construct(private string $id)
    {
        preg_match("#CVE-(\d+)-(\d+)#", $id, $matches);
        $this->year = (int) $matches[1];
        $this->sequenceNumber = (int) $matches[2];
    }

    public static function fromString(?string $cveId): ?CveId
    {
        if (empty($cveId) || !preg_match("#CVE-(\d+)-(\d+)#i", $cveId, $matches)) {
            return null;
        }
        return new CveId(strtoupper($cveId));
    }

    /**
     * @param CveId[] $cveIds
     * @return CveId[]
     */
    public static function sort(array $cveIds): array
    {
        $sortedCveIds = array_merge([], $cveIds);
        usort($sortedCveIds, fn (CveId $first, CveId $second): int => $first->compareTo($second));
        return $sortedCveIds;
    }

    public function compareTo(CveId $otherCveId): int
    {
        if ($this->year !== $otherCveId->year) {
            return $this->year - $otherCveId->year;
        }
        return $this->sequenceNumber - $otherCveId->sequenceNumber;
    }

    public function getId(): string
    {
        return $this->id;
    }

    
    public function __toString(): string
    {
        return $this->id;
    }

    
    public function jsonSerialize(): string
    {
        return (string)$this;
    }
}
