<?php

declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit;

final class CveDetails implements \JsonSerializable
{
    public function __construct(
        private CveId $id,
        private ?float $baseScore,
        private ?string $publishedDate,
        private ?string $lastModifiedDate,
        private ?string $description
    ) {
    }

    public function getId(): CveId
    {
        return $this->id;
    }

    
    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "baseScore" => $this->baseScore,
            "publishedDate" => $this->publishedDate,
            "lastModifiedDate" => $this->lastModifiedDate,
            "description" => $this->description,
        ];
    }
}
