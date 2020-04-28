<?php
declare(strict_types=1);


namespace lightswitch05\PhpVersionAudit;


final class CveId implements \JsonSerializable
{
    /**
     * @var string $id
     */
    private $id;

    /**
     * @var int $year
     */
    private $year;

    /**
     * @var int $sequenceNumber
     */
    private $sequenceNumber;

    /**
     * @param string $id
     */
    private function __construct(string $id)
    {
        $this->id = $id;
        preg_match("#CVE-(\d+)-(\d+)#", $id, $matches);
        $this->year = (int) $matches[1];
        $this->sequenceNumber = (int) $matches[2];
    }

    /**
     * @param string|null $cveId
     * @return CveId|null
     */
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
        usort($sortedCveIds, function(CveId $first, CveId $second): int {
            /** @var CveId $first, @var CveId $second */
            return $first->compareTo($second);
        });
        return $sortedCveIds;
    }

    /**
     * @param CveId $otherCveId
     * @return int
     */
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
       return (string)$this;
    }
}
