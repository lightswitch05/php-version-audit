<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit;

final class CveDetails implements \JsonSerializable
{
    /**
     * @var CveId $id
     */
    private $id;

    /**
     * @var float|null $baseScore
     */
    private $baseScore;

    /**
     * @var string|null $publishedDate
     */
    private $publishedDate;

    /**
     * @var string|null $lastModifiedDate
     */
    private $lastModifiedDate;

    /**
     * @var string|null $description
     */
    private $description;

    /**
     * Cve constructor.
     * @param CveId $id
     * @param float|null $baseScore
     * @param string|null $publishedDate
     * @param string|null $lastModifiedDate
     * @param string|null $description
     */
    public function __construct(CveId $id, ?float $baseScore, ?string $publishedDate, ?string $lastModifiedDate, ?string $description)
    {
        $this->id = $id;
        $this->baseScore = $baseScore;
        $this->publishedDate = $publishedDate;
        $this->lastModifiedDate = $lastModifiedDate;
        $this->description = $description;
    }

    /**
     * @return CveId
     */
    public function getId(): CveId
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "baseScore" => $this->baseScore,
            "publishedDate" => $this->publishedDate,
            "lastModifiedDate" => $this->lastModifiedDate,
            "description" => $this->description
        ];
    }
}
