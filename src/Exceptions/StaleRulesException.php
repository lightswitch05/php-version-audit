<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;


class StaleRulesException extends \DomainException
{
    /**
     * @param string|null $details
     * @return StaleRulesException
     */
    public static function fromString(?string $details)
    {
        return new self("Rules are stale: $details");
    }
}
