<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;


final class InvalidVersionException extends \InvalidArgumentException
{
    public static function fromString(?string $version): InvalidVersionException
    {
        return new self("PhpVersion [$version] is not valid");
    }
}
