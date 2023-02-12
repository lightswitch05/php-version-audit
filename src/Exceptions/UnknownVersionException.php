<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;


final class UnknownVersionException extends StaleRulesException
{
    /**
     * @param string|null $details
     * @return UnknownVersionException
     */
    public static function fromString(?string $details): UnknownVersionException
    {
        return new self("Unknown PHP version ($details), perhaps rules are stale.");
    }
}
