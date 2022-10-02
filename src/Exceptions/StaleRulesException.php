<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;


use DomainException;

class StaleRulesException extends DomainException
{
    /**
     * @param string|null $details
     * @return StaleRulesException
     */
    public static function fromString(?string $details): StaleRulesException
    {
        return new self("Rules are stale: $details");
    }
}
