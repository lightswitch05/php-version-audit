<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;


final class UnknownVersionException extends StaleRulesException
{
    /**
     * @param string|null $version
     * @return UnknownVersionException
     */
    public static function fromString(?string $version): UnknownVersionException
    {
        return new self("Unknown PHP version ($version), perhaps rules are stale.");
    }
}
