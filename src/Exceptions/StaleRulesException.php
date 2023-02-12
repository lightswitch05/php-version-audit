<?php

declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;

class StaleRulesException extends \DomainException
{
    /**
     * @return StaleRulesException
     */
    public static function fromString(?string $details)
    {
        return new self("Rules are stale: $details");
    }
}
