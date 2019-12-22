<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;


final class ParseException extends \ErrorException
{
    /**
     * @param string|null message
     * @return ParseException
     */
    public static function fromString(?string $message): ParseException
    {
        return new self("Parse error: $message");
    }
}
