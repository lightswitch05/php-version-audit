<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;


final class InvalidArgumentException extends \InvalidArgumentException
{
    /**
     * @param string $message
     * @return InvalidArgumentException
     */
    public static function fromString(string $message): InvalidArgumentException
    {
        return new self($message);
    }
}
