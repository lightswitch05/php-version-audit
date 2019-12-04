<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;


final class InvalidVersionException extends \InvalidArgumentException
{
    /**
     * @param string|null $version
     * @return InvalidVersionException
     */
    public static function fromString(?string $version): InvalidVersionException
    {
        return new self(sprintf('PhpVersion [%s] is not valid', $version));
    }
}
